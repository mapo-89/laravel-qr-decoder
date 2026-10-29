import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

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

    def decode(self):
        return subprocess.run(
            [sys.executable, str(Path(__file__).with_name("decode_qr.py")), str(self.path)],
            capture_output=True,
            text=True,
            timeout=15,
        )

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
