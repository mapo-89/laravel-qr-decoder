import sys
import numpy as np
from PIL import Image, ImageOps
import zxingcpp


def try_decode(pil_img):
    results = zxingcpp.read_barcodes(
        np.asarray(pil_img.convert("RGB")), formats=zxingcpp.BarcodeFormat.QRCode
    )
    values = {result.text.strip() for result in results if result.text.strip()}
    if len(values) > 1:
        raise ValueError("Ambiguous QR image")
    return next(iter(values), "")


def preprocess(pil_img):
    image = pil_img.convert("L")
    image = image.resize((image.width * 2, image.height * 2), Image.Resampling.LANCZOS)
    image = ImageOps.autocontrast(image)
    return image.point(lambda pixel: 255 if pixel > 140 else 0).convert("RGB")


def main():
    if len(sys.argv) != 2:
        return 1
    try:
        with Image.open(sys.argv[1]) as source:
            if source.width * source.height > 16777216:
                raise ValueError("Image too large")
            image = ImageOps.exif_transpose(source)
            image.thumbnail((2048, 2048), Image.Resampling.LANCZOS)
            result = try_decode(image)
            if not result:
                result = try_decode(preprocess(image))
            print(result)
        return 0
    except Exception:
        print("Unable to decode QR image.", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
