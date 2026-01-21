import sys
import numpy as np
from PIL import Image, ImageOps
import zxingcpp  # pip install zxing-cpp

def try_decode(pil_img):
    img = np.array(pil_img.convert("RGB"))
    barcodes = zxingcpp.read_barcodes(img)
    if not barcodes:
        return ""
    # Nimm den ersten gefundenen QR
    return (barcodes[0].text or "").strip()

def preprocess(pil_img):
    # Hilft oft bei Logos/kontrastarmen Codes:
    # - größer skalieren
    # - graustufen + autocontrast
    # - leicht schärfen über Kontrast / Threshold
    img = pil_img.convert("L")
    img = img.resize((img.width * 2, img.height * 2), Image.Resampling.LANCZOS)
    img = ImageOps.autocontrast(img)
    # einfacher Threshold
    img = img.point(lambda p: 255 if p > 140 else 0)
    return img.convert("RGB")

def main():
    if len(sys.argv) < 2:
        print("")
        return

    path = sys.argv[1]
    pil = Image.open(path)

    out = try_decode(pil)
    if out:
        print(out)
        return

    out = try_decode(preprocess(pil))
    print(out)

if __name__ == "__main__":
    main()
