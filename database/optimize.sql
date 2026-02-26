-- Performance Optimization Indexes
-- Run this to improve query performance

USE connecthub;

-- Users table indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_created ON users(created_at);

-- Posts table indexes
CREATE INDEX idx_posts_user_created ON posts(user_id, created_at);
CREATE INDEX idx_posts_sentiment ON posts(sentiment_score);

-- Comments table indexes
CREATE INDEX idx_comments_post_created ON comments(post_id, created_at);

-- Likes table indexes
CREATE INDEX idx_likes_post_user ON likes(post_id, user_id);
CREATE INDEX idx_likes_type ON likes(like_type);

-- Ratings table indexes
CREATE INDEX idx_ratings_post ON ratings(post_id);

-- Sessions table indexes
CREATE INDEX idx_sessions_expires ON sessions(expires_at);

-- Show all indexes
SHOW INDEX FROM users;
SHOW INDEX FROM posts;
SHOW INDEX FROM comments;
SHOW INDEX FROM likes;
SHOW INDEX FROM ratings;