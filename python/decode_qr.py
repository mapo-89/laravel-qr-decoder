import json
import sys

import numpy as np
import zxingcpp
from PIL import Image, ImageOps, UnidentifiedImageError


class DecodeError(Exception):
    def __init__(self, reason):
        self.reason = reason
        super().__init__(reason)


def on_white(pil_img):
    if pil_img.mode in ("RGBA", "LA") or "transparency" in pil_img.info:
        rgba = pil_img.convert("RGBA")
        background = Image.new("RGBA", rgba.size, "white")
        return Image.alpha_composite(background, rgba).convert("RGB")
    return pil_img.convert("RGB")


def try_decode(pil_img):
    results = zxingcpp.read_barcodes(
        np.asarray(on_white(pil_img)), formats=zxingcpp.BarcodeFormat.QRCode
    )
    values = {result.text for result in results}
    if len(values) > 1:
        raise DecodeError("ambiguous_image")
    return next(iter(values), "")


def preprocess(pil_img):
    image = on_white(pil_img).convert("L")
    image = image.resize((image.width * 2, image.height * 2), Image.Resampling.LANCZOS)
    image = ImageOps.autocontrast(image)
    return image.point(lambda pixel: 255 if pixel > 140 else 0).convert("RGB")


def decode(path):
    with Image.open(path) as source:
        if source.width * source.height > 16777216:
            raise DecodeError("image_too_large")
        image = on_white(ImageOps.exif_transpose(source))
        image.thumbnail((2048, 2048), Image.Resampling.LANCZOS)
        result = try_decode(image)
        if not result:
            result = try_decode(preprocess(image))
        return result


def main():
    # Pipes on Windows otherwise use the active ANSI codepage on Python 3.12–3.14.
    sys.stdout.reconfigure(encoding="utf-8")
    json_mode = len(sys.argv) == 3 and sys.argv[1] == "--json"
    if not json_mode and len(sys.argv) != 2:
        print("Usage: decode_qr.py [--json] IMAGE", file=sys.stderr)
        return 1
    try:
        result = decode(sys.argv[-1])
        print(json.dumps({"text": result}) if json_mode else result)
        return 0
    except DecodeError as error:
        reason = error.reason
    except Image.DecompressionBombError:
        reason = "image_too_large"
    except (UnidentifiedImageError, OSError, ValueError):
        reason = "invalid_image"
    except Exception:
        reason = "process_failed"
    if json_mode:
        print(json.dumps({"error": reason}))
    else:
        print("Unable to decode QR image.", file=sys.stderr)
    return 1


if __name__ == "__main__":
    sys.exit(main())
