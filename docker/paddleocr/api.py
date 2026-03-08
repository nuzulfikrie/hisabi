#!/usr/bin/env python3
"""
PaddleOCR Flask API Service
Receives images via HTTP POST and returns extracted text
"""

import os
import tempfile
import logging
from flask import Flask, request, jsonify
from paddleocr import PaddleOCR
from PIL import Image
import io

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Initialize PaddleOCR with multiple languages
# Supports: en (English), ms (Malay), ar (Arabic), etc.
logger.info("Initializing PaddleOCR...")
try:
    ocr = PaddleOCR(
        use_angle_cls=True,  # Enable angle classification for rotated text
        lang='en',           # Primary language
        use_gpu=False,       # Use CPU (set to True if GPU available)
        show_log=False       # Reduce noise
    )
    logger.info("PaddleOCR initialized successfully")
except Exception as e:
    logger.error(f"Failed to initialize PaddleOCR: {e}")
    raise


def preprocess_image(image_path: str) -> str:
    """
    Preprocess image for better OCR accuracy
    - Convert to RGB if necessary
    - Save as temporary file
    """
    try:
        with Image.open(image_path) as img:
            # Convert to RGB if necessary (handles PNG with alpha, CMYK, etc.)
            if img.mode != 'RGB':
                img = img.convert('RGB')
                # Save to temp file
                temp_path = image_path + '_processed.jpg'
                img.save(temp_path, 'JPEG', quality=95)
                return temp_path
        return image_path
    except Exception as e:
        logger.warning(f"Image preprocessing failed: {e}, using original")
        return image_path


def extract_text_from_result(result) -> dict:
    """
    Extract text and metadata from PaddleOCR result
    """
    if not result or not result[0]:
        return {
            'text': '',
            'lines': [],
            'confidence': 0.0,
            'word_count': 0
        }
    
    lines = []
    total_confidence = 0.0
    word_count = 0
    
    for line in result[0]:
        if line:
            bbox = line[0]  # Bounding box coordinates
            text_info = line[1]  # (text, confidence)
            text = text_info[0]
            confidence = text_info[1]
            
            lines.append({
                'text': text,
                'confidence': round(confidence, 4),
                'bbox': bbox
            })
            
            total_confidence += confidence
            word_count += len(text.split())
    
    full_text = '\n'.join([line['text'] for line in lines])
    avg_confidence = total_confidence / len(lines) if lines else 0.0
    
    return {
        'text': full_text,
        'lines': lines,
        'confidence': round(avg_confidence, 4),
        'word_count': word_count,
        'line_count': len(lines)
    }


@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'paddleocr',
        'version': '1.0.0'
    })


@app.route('/ocr', methods=['POST'])
def ocr_endpoint():
    """
    Main OCR endpoint
    Accepts: multipart/form-data with 'image' field
    Returns: JSON with extracted text and metadata
    """
    try:
        # Check if image is provided
        if 'image' not in request.files:
            logger.warning("No image file in request")
            return jsonify({
                'error': 'No image file provided',
                'text': '',
                'lines': [],
                'confidence': 0.0
            }), 400
        
        file = request.files['image']
        
        if file.filename == '':
            logger.warning("Empty filename")
            return jsonify({
                'error': 'Empty filename',
                'text': '',
                'lines': [],
                'confidence': 0.0
            }), 400
        
        # Save uploaded file temporarily
        with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as tmp:
            file.save(tmp.name)
            temp_path = tmp.name
        
        try:
            # Preprocess image
            processed_path = preprocess_image(temp_path)
            
            # Perform OCR
            logger.info(f"Processing image: {file.filename}")
            result = ocr.ocr(processed_path, cls=True)
            
            # Extract text and metadata
            extracted = extract_text_from_result(result)
            
            logger.info(f"OCR complete: {extracted['word_count']} words, "
                       f"confidence: {extracted['confidence']}")
            
            return jsonify(extracted)
            
        finally:
            # Cleanup temporary files
            try:
                os.unlink(temp_path)
                if processed_path != temp_path and os.path.exists(processed_path):
                    os.unlink(processed_path)
            except Exception as e:
                logger.warning(f"Cleanup error: {e}")
                
    except Exception as e:
        logger.error(f"OCR processing error: {e}", exc_info=True)
        return jsonify({
            'error': str(e),
            'text': '',
            'lines': [],
            'confidence': 0.0
        }), 500


@app.route('/ocr/base64', methods=['POST'])
def ocr_base64_endpoint():
    """
    OCR endpoint for base64 encoded images
    Accepts: JSON with 'image' field containing base64 string
    Returns: JSON with extracted text and metadata
    """
    try:
        data = request.get_json()
        
        if not data or 'image' not in data:
            return jsonify({
                'error': 'No base64 image provided',
                'text': '',
                'lines': [],
                'confidence': 0.0
            }), 400
        
        import base64
        
        # Decode base64
        image_data = base64.b64decode(data['image'])
        
        # Save to temp file
        with tempfile.NamedTemporaryFile(delete=False, suffix='.jpg') as tmp:
            tmp.write(image_data)
            temp_path = tmp.name
        
        try:
            # Perform OCR
            result = ocr.ocr(temp_path, cls=True)
            extracted = extract_text_from_result(result)
            
            return jsonify(extracted)
            
        finally:
            try:
                os.unlink(temp_path)
            except:
                pass
                
    except Exception as e:
        logger.error(f"Base64 OCR error: {e}")
        return jsonify({
            'error': str(e),
            'text': '',
            'lines': [],
            'confidence': 0.0
        }), 500


if __name__ == '__main__':
    # Development server (not for production)
    app.run(host='0.0.0.0', port=8000, debug=False)
