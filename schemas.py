from pydantic import BaseModel, EmailStr, Field
from typing import Optional
from datetime import datetime

# Token schemas
class Token(BaseModel):
    access_token: str
    token_type: str
    user_id: int
    is_admin: bool

class TokenData(BaseModel):
    username: Optional[str] = None

# User schemas
class UserBase(BaseModel):
    username: str
    email: str
    fullname: str

class UserCreate(UserBase):
    password: str

class UserLogin(BaseModel):
    username: str
    password: str

class UserUpdate(BaseModel):
    fullname: Optional[str] = None
    email: Optional[str] = None
    password: Optional[str] = None

class User(UserBase):
    id: int
    created_at: datetime
    is_admin: bool
    
    class Config:
        orm_mode = True 