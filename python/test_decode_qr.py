import json
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

import numpy as np
from PIL import Image
import zxingcpp


class DecodeQrTest(unittest.TestCase):
    payload = "https://example.com/qr-decoder?token=test-123"

    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.path = Path(self.directory.name) / "image"

    @staticmethod
    def qr_image(payload):
        barcode = zxingcpp.create_barcode(payload, zxingcpp.BarcodeFormat.QRCode)
        return Image.fromarray(barcode.to_image(scale=6)).convert("RGB")

    def decode(self, json_mode=False, env=None):
        return subprocess.run(
            [sys.executable, str(Path(__file__).with_name("decode_qr.py"))]
            + (["--json"] if json_mode else []) + [str(self.path)],
            capture_output=True,
            text=True,
            timeout=15,
            encoding="utf-8",
            env=env,
        )

    def test_preserves_whitespace_and_unicode_even_with_non_utf8_parent(self):
        payload = "  ä 日本語\nsecond line\t  "
        self.qr_image(payload).save(self.path, format="PNG")
        env = dict(os.environ, PYTHONIOENCODING="cp1252")
        result = self.decode(json_mode=True, env=env)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(json.loads(result.stdout), {"text": payload})
        result = self.decode(env=env)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, payload + "\n")

    def test_accepts_a_whitespace_only_payload(self):
        self.qr_image("   ").save(self.path, format="PNG")
        result = self.decode(json_mode=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(json.loads(result.stdout), {"text": "   "})

    def test_accepts_repeated_copies_of_the_same_payload(self):
        qr = self.qr_image(self.payload)
        image = Image.new("RGB", (qr.width * 2 + 40, qr.height), "white")
        image.paste(qr, (0, 0))
        image.paste(qr, (qr.width + 40, 0))
        image.save(self.path, format="PNG")
        result = self.decode(json_mode=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(json.loads(result.stdout), {"text": self.payload})

    def test_decodes_transparent_images_on_a_white_background(self):
        for mode in ("RGBA", "LA", "P"):
            with self.subTest(mode=mode):
                image = self.qr_image(self.payload).convert("RGBA")
                pixels = np.asarray(image).copy()
                white = pixels[:, :, 0] > 200
                pixels[white] = (0, 0, 0, 0)
                image = Image.fromarray(pixels)
                if mode == "LA":
                    image = image.convert("LA")
                elif mode == "P":
                    image = image.convert("P")
                image.save(self.path, format="PNG")
                result = self.decode(json_mode=True)
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(json.loads(result.stdout), {"text": self.payload})

    def test_rejects_distinct_payloads_that_only_differ_in_whitespace(self):
        first = self.qr_image("token")
        second = self.qr_image(" token ")
        image = Image.new("RGB", (first.width + second.width + 40, max(first.height, second.height)), "white")
        image.paste(first, (0, 0))
        image.paste(second, (first.width + 40, 0))
        image.save(self.path, format="PNG")
        result = self.decode(json_mode=True)
        self.assertEqual(result.returncode, 1)
        self.assertEqual(json.loads(result.stdout), {"error": "ambiguous_image"})
        self.assertEqual(result.stderr, "")

    def test_json_reports_invalid_images_without_disclosing_contents(self):
        self.path.write_bytes(b"private-image-content")
        result = self.decode(json_mode=True)
        self.assertEqual(result.returncode, 1)
        self.assertEqual(json.loads(result.stdout), {"error": "invalid_image"})
        self.assertEqual(result.stderr, "")

    def test_rejects_oversized_images_before_loading_pixels(self):
        # A header is sufficient: no large pixel allocation is needed for this check.
        import struct
        import zlib
        def chunk(kind, data):
            return struct.pack(">I", len(data)) + kind + data + struct.pack(">I", zlib.crc32(kind + data))
        header = struct.pack(">IIBBBBB", 4097, 4096, 8, 2, 0, 0, 0)
        self.path.write_bytes(b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", header) + chunk(b"IDAT", b"") + chunk(b"IEND", b""))
        result = self.decode(json_mode=True)
        self.assertEqual(result.returncode, 1)
        self.assertEqual(json.loads(result.stdout), {"error": "image_too_large"})

    def test_decodes_supported_image_formats(self):
        for image_format in ("PNG", "JPEG", "WEBP"):
            with self.subTest(image_format=image_format):
                self.qr_image(self.payload).save(self.path, format=image_format)
                result = self.decode()
                self.assertEqual(result.returncode, 0, result.stderr)
                self.assertEqual(result.stdout.strip(), self.payload)
                self.assertEqual(result.stderr, "")

    def test_decodes_image_with_exif_orientation(self):
        exif = Image.Exif()
        exif[274] = 6
        self.qr_image(self.payload).save(self.path, format="JPEG", exif=exif)
        result = self.decode()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), self.payload)

    def test_returns_empty_output_for_an_image_without_a_qr_code(self):
        Image.new("RGB", (128, 128), "white").save(self.path, format="PNG")
        result = self.decode()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout.strip(), "")

    def test_rejects_images_with_different_qr_codes(self):
        first = self.qr_image(self.payload)
        second = self.qr_image("a-different-payload")
        image = Image.new("RGB", (first.width + second.width + 40, max(first.height, second.height)), "white")
        image.paste(first, (0, 0))
        image.paste(second, (first.width + 40, 0))
        image.save(self.path, format="PNG")
        result = self.decode()
        self.assertEqual(result.returncode, 1)
        self.assertEqual(result.stdout, "")
        self.assertEqual(result.stderr.strip(), "Unable to decode QR image.")

    def test_rejects_invalid_images(self):
        self.path.write_bytes(b"invalid image data")
        result = self.decode()
        self.assertEqual(result.returncode, 1)
        self.assertEqual(result.stdout, "")
        self.assertEqual(result.stderr.strip(), "Unable to decode QR image.")


if __name__ == "__main__":
    unittest.main()
