"""Generate real fixtures for the PHP-to-Python integration tests."""

import json
import sys

import zxingcpp
from PIL import Image

path, payload, image_format = sys.argv[1:]
barcode = zxingcpp.create_barcode(json.loads(payload), zxingcpp.BarcodeFormat.QRCode)
Image.fromarray(barcode.to_image(scale=6)).convert("RGB").save(
    path, format=image_format
)
