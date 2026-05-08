"""
Surya OCR microservice — surya-ocr 0.17.x predictor API.
Accepts a base64-encoded PDF, returns extracted text per page.

Language codes come from surya/recognition/languages.py.
Configure via SURYA_LANGUAGES env var (comma-separated, e.g. "ar,fr,en").
"""

import base64
import os
import tempfile
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException
from pdf2image import convert_from_path
from pydantic import BaseModel

# ── Language config ───────────────────────────────────────────────────────────
_raw = os.getenv("SURYA_LANGUAGES", "ar,fr")
LANGUAGES: list[str] = [lang.strip() for lang in _raw.split(",") if lang.strip()]

# ── Model handles (populated during startup) ──────────────────────────────────
foundation_predictor  = None
recognition_predictor = None
detection_predictor   = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Load all Surya predictors once at startup; keep them in memory forever."""
    global foundation_predictor, recognition_predictor, detection_predictor

    print(f"Loading Surya OCR models (languages: {LANGUAGES})...")

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


# ── Routes ────────────────────────────────────────────────────────────────────

@app.post("/ocr")
def ocr(request: OcrRequest):
    tmp_path = None
    try:
        pdf_bytes = base64.b64decode(request.pdf_base64)

        with tempfile.NamedTemporaryFile(suffix=".pdf", delete=False) as tmp:
            tmp.write(pdf_bytes)
            tmp_path = tmp.name

        pages_images = convert_from_path(tmp_path, dpi=300)

        # langs: one list of language codes per page
        langs = [LANGUAGES] * len(pages_images)

        results = recognition_predictor(
            pages_images,
            det_predictor=detection_predictor,
            langs=langs,
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
        raise HTTPException(status_code=500, detail=str(e))

    finally:
        if tmp_path and os.path.exists(tmp_path):
            os.unlink(tmp_path)


@app.get("/health")
def health():
    return {"status": "ok"}