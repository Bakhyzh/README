from openai import OpenAI
import logging
from typing import List
import os
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

# Configure logging
logger = logging.getLogger(__name__)

class AIAssistant:
    def __init__(self):
        # Get API key from environment variables
        api_key = os.getenv("OPENAI_API_KEY")
        if not api_key:
            logger.error("OpenAI API key not found in environment variables")
        
        # Initialize OpenAI client
        self.client = OpenAI(api_key=api_key)
        
        # Set default model (can be configured in .env)
        self.model = os.getenv("MODEL_NAME", "gpt-3.5-turbo")
        
        # System message to define AI assistant behavior
        self.system_message = {
            "role": "system", 
            "content": "You are a helpful AI assistant for a portfolio website. Provide concise, informative answers about resume building, career advice, technology, and related topics."
        }
    
    async def get_response(self, user_query: str, conversation_history: List[dict] = None) -> str:
        """
        Get a response from the AI assistant.
        
        Args:
            user_query: The user's question or message
            conversation_history: Optional list of previous messages in the conversation
        
        Returns:
            The AI's response as a string
        """
        try:
            # Check if OpenAI client is properly initialized
            if not hasattr(self, 'client') or not self.client:
                return "AI assistant is not configured. Please set the OPENAI_API_KEY in your .env file."
            
            # Initialize with system message
            messages = [self.system_message]
            
            # Add conversation history if provided
            if conversation_history:
                messages.extend(conversation_history)
            
            # Add the current user query
            messages.append({"role": "user", "content": user_query})
            
            # Call the OpenAI API with the new format
            response = self.client.chat.completions.create(
                model=self.model,
                messages=messages,
                max_tokens=int(os.getenv("MAX_TOKENS", "1000")),
                temperature=float(os.getenv("TEMPERATURE", "0.7")),
            )
            
            # Extract and return the assistant's response (new format)
            ai_response = response.choices[0].message.content
            return ai_response
            
        except Exception as e:
            logger.error(f"Error getting AI response: {str(e)}")
            return f"Sorry, I encountered an error processing your request: {str(e)}"

# Create a single instance of the assistant
assistant = AIAssistant()