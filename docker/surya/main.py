"""
Surya OCR microservice — surya-ocr 0.17.x predictor API.
Accepts a base64-encoded PDF, returns extracted text per page.

Surya 0.17.x no longer accepts per-request language hints on the
RecognitionPredictor call. The model handles multilingual OCR internally.
"""

import base64
import logging
import os
import tempfile
from contextlib import asynccontextmanager

import numpy as np
from fastapi import FastAPI, HTTPException
from PIL import Image
from pydantic import BaseModel
from surya.common.surya.schema import TaskNames
from surya.input.load import load_from_file
from surya.settings import settings

logger = logging.getLogger(__name__)


def env_bool(name: str, default: bool = False) -> bool:
    value = os.getenv(name)
    if value is None:
        return default

    return value.strip().lower() in {"1", "true", "yes", "on"}


IMAGE_DPI = int(os.getenv("SURYA_IMAGE_DPI", str(settings.IMAGE_DPI)))
HIGHRES_IMAGE_DPI = int(os.getenv("SURYA_HIGHRES_IMAGE_DPI", str(settings.IMAGE_DPI_HIGHRES)))
MATH_MODE = env_bool("SURYA_MATH_MODE", False)
REMOVE_RED_STAMP = env_bool("SURYA_REMOVE_RED_STAMP", False)

# ── Model handles (populated during startup) ──────────────────────────────────
foundation_predictor  = None
recognition_predictor = None
detection_predictor   = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load all Surya predictors once at startup; keep them in memory forever."""
    global foundation_predictor, recognition_predictor, detection_predictor

    print(
        "Loading Surya OCR models "
        f"(image_dpi={IMAGE_DPI}, highres_image_dpi={HIGHRES_IMAGE_DPI}, "
        f"math_mode={MATH_MODE}, remove_red_stamp={REMOVE_RED_STAMP})..."
    )

    from surya.foundation import FoundationPredictor
    from surya.recognition import RecognitionPredictor
    from surya.detection import DetectionPredictor

    # FoundationPredictor = shared backbone weights
    # RecognitionPredictor builds on top of it (text recognition)
    # DetectionPredictor is independent (text line detection)
    foundation_predictor  = FoundationPredictor()
    recognition_predictor = RecognitionPredictor(foundation_predictor)
    detection_predictor   = DetectionPredictor()

    print("Surya models ready.")
    yield


app = FastAPI(lifespan=lifespan)


# ── Schemas ───────────────────────────────────────────────────────────────────

class OcrRequest(BaseModel):
    pdf_base64: str


# ── Image preprocessing ───────────────────────────────────────────────────────

def remove_red_stamp(image: Image.Image) -> Image.Image:
    """Remove strong red stamp pixels without touching normal black text."""
    arr = np.array(image.convert("RGB"))
    red = arr[:, :, 0].astype(np.int16)
    green = arr[:, :, 1].astype(np.int16)
    blue = arr[:, :, 2].astype(np.int16)

    mask = (red > 135) & (red > green + 35) & (red > blue + 35)
    arr[mask] = [255, 255, 255]

    return Image.fromarray(arr)


def preprocess_images(images: list[Image.Image]) -> list[Image.Image]:
    if not REMOVE_RED_STAMP:
        return images

    return [remove_red_stamp(image) for image in images]


# ── Routes ────────────────────────────────────────────────────────────────────

@app.post("/ocr")
def ocr(request: OcrRequest):
    tmp_path = None
    try:
        pdf_bytes = base64.b64decode(request.pdf_base64)

        with tempfile.NamedTemporaryFile(suffix=".pdf", delete=False) as tmp:
            tmp.write(pdf_bytes)
            tmp_path = tmp.name

        pages_images, _ = load_from_file(tmp_path, dpi=IMAGE_DPI)
        highres_images, _ = load_from_file(tmp_path, dpi=HIGHRES_IMAGE_DPI)
        pages_images = preprocess_images(pages_images)
        highres_images = preprocess_images(highres_images)

        results = recognition_predictor(
            pages_images,
            task_names=[TaskNames.ocr_with_boxes] * len(pages_images),
            det_predictor=detection_predictor,
            highres_images=highres_images,
            sort_lines=True,
            math_mode=MATH_MODE,
        )

        return {
            "pages": [
                {
                    "page": i + 1,
                    "text": "\n".join(
                        line.text
                        for line in result.text_lines
                        if line.text.strip()
                    ),
                }
                for i, result in enumerate(results)
            ]
        }

    except Exception as e:
        logger.exception("OCR request failed")
        raise HTTPException(status_code=500, detail=str(e))

    finally:
        if tmp_path and os.path.exists(tmp_path):
            os.unlink(tmp_path)


@app.get("/health")
def health():
    return {"status": "ok"}
