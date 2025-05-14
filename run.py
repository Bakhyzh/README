import uvicorn
import config

if __name__ == "__main__":
    # Run the FastAPI application
    uvicorn.run(
        "main:app", 
        host="0.0.0.0", 
        port=config.PORT,
        reload=config.DEBUG
    ) 