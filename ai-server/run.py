"""Start the AI server with uvicorn."""
import os
from dotenv import load_dotenv

load_dotenv()

if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        "main:app",
        host=os.getenv("HOST", "127.0.0.1"),
        port=int(os.getenv("PORT", "8100")),
        workers=int(os.getenv("WORKERS", "2")),
        log_level="info",
    )
