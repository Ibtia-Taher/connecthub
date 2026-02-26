/**
 * Feed Page JavaScript
 * Handles post creation and feed loading
 */

let currentPage = 1;
let isLoading = false;
let hasMore = true;

// State for media upload
let uploadedMediaUrl = null;
let uploadedMediaType = null;
let youtubeUrl = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Feed page loaded');
    
    initializeCreatePost();
    loadPosts();
    initializeInfiniteScroll();
});

/**
 * Initialize create post functionality
 */
function initializeCreatePost() {
    const postContent = document.getElementById('postContent');
    const createPostBtn = document.getElementById('createPostBtn');
    const addPhotoBtn = document.getElementById('addPhotoBtn');
    const addVideoBtn = document.getElementById('addVideoBtn');
    const mediaInput = document.getElementById('mediaInput');
    
    // Photo upload
    addPhotoBtn.addEventListener('click', function() {
        mediaInput.click();
    });
    
    mediaInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            uploadPostMedia(file);
        }
    });
    
    // Remove media
    const removeMediaBtn = document.getElementById('removeMediaBtn');
    removeMediaBtn.addEventListener('click', function() {
        clearMedia();
    });
    
    // YouTube embed
    addVideoBtn.addEventListener('click', function() {
        const container = document.getElementById('youtubeInputContainer');
        if (container.style.display === 'none') {
            container.style.display = 'block';
            addVideoBtn.textContent = '❌ Cancel Video';
        } else {
            container.style.display = 'none';
            addVideoBtn.textContent = '🎥 YouTube';
            document.getElementById('youtubeInput').value = '';
            youtubeUrl = null;
        }
    });
    
    // Create post
    createPostBtn.addEventListener('click', createPost);
    
    // Allow Enter to post (with Shift+Enter for new line)
    postContent.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            createPost();
        }
    });
}

/**
 * Upload post media
 */
async function uploadPostMedia(file) {
    const formData = new FormData();
    formData.append('media', file);
    
    try {
        showCreatePostMessage('Uploading image...', 'success');
        
        const response = await fetch(window.API_BASE + '/posts/upload-media.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            uploadedMediaUrl = data.data.filename;
            uploadedMediaType = 'image';
            
            // Show preview
            document.getElementById('previewImage').src = data.data.url;
            document.getElementById('mediaPreview').style.display = 'block';
            
            showCreatePostMessage('Image uploaded!', 'success');
        } else {
            showCreatePostMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showCreatePostMessage('Failed to upload image', 'error');
    }
}

/**
 * Clear media
 */
function clearMedia() {
    uploadedMediaUrl = null;
    uploadedMediaType = null;
    document.getElementById('mediaPreview').style.display = 'none';
    document.getElementById('previewImage').src = '';
    document.getElementById('mediaInput').value = '';
}


/**
 * Create post (WITH SENTIMENT ANALYSIS)
 */
async function createPost() {
    const content = document.getElementById('postContent').value.trim();
    const youtubeInput = document.getElementById('youtubeInput').value.trim();
    const createPostBtn = document.getElementById('createPostBtn');
    
    // Validate
    if (!content && !uploadedMediaUrl && !youtubeInput) {
        showCreatePostMessage('Please write something or add media', 'error');
        return;
    }
    
    if (content.length > 5000) {
        showCreatePostMessage('Post is too long (max 5000 characters)', 'error');
        return;
    }
    
    // Analyze sentiment if there's text content
    let sentimentScore = null;
    if (content) {
        const sentiment = analyzePostSentiment(content);
        sentimentScore = sentiment.score;
        
        console.log('Sentiment Analysis:', {
            content: content,
            score: sentimentScore,
            category: sentiment.category
        });
    }
    
    // Prepare data
    const postData = {
        content: content,
        sentiment_score: sentimentScore
    };
    
    if (uploadedMediaUrl) {
        postData.media_url = uploadedMediaUrl;
        postData.media_type = uploadedMediaType;
    }
    
    if (youtubeInput) {
        postData.youtube_embed = youtubeInput;
    }
    
    // Disable button
    createPostBtn.disabled = true;
    createPostBtn.textContent = 'Posting...';
    
    try {
        const response = await fetch(window.API_BASE + '/posts/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(postData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showCreatePostMessage('Post created!', 'success');
            
            // Clear form
            document.getElementById('postContent').value = '';
            clearMedia();
            document.getElementById('youtubeInputContainer').style.display = 'none';
            document.getElementById('youtubeInput').value = '';
            document.getElementById('addVideoBtn').textContent = '🎥 YouTube';
            
            // Add new post to top of feed with animation
            const postsFeed = document.getElementById('postsFeed');
            const newPostElement = createPostElement(data.data.post);
            
            // Add is_owner flag for the new post
            data.data.post.is_owner = true;
            
            // Insert at the beginning
            if (postsFeed.firstChild) {
                postsFeed.insertBefore(newPostElement, postsFeed.firstChild);
            } else {
                postsFeed.appendChild(newPostElement);
            }
            
            // Animate the new post
            if (typeof animateNewPost === 'function') {
                animateNewPost(newPostElement);
            }
            
            // Clear message after 3 seconds
            setTimeout(() => {
                document.getElementById('createPostMessage').style.display = 'none';
            }, 3000);
        }
        
        else {
            showCreatePostMessage(data.message, 'error');
        }
    } catch (error) {
        console.error('Create post error:', error);
        showCreatePostMessage('Failed to create post', 'error');
    } finally {
        createPostBtn.disabled = false;
        createPostBtn.textContent = 'Post';
    }
}

/**
 * Load posts (WITH ANIMATIONS)
 */
async function loadPosts(refresh = false) {
    if (isLoading) return;
    
    isLoading = true;
    const postsFeed = document.getElementById('postsFeed');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    
    if (refresh) {
        currentPage = 1;
        postsFeed.innerHTML = '<div class="loading-spinner">Loading posts...</div>';
        hasMore = true;
    }
    
    try {
        const response = await fetch(
            `${window.API_BASE}/posts/get-posts.php?page=${currentPage}&limit=10`
        );
        
        const data = await response.json();
        
        if (data.success) {
            const posts = data.data.posts;
            const pagination = data.data.pagination;
            
            if (refresh) {
                postsFeed.innerHTML = '';
            }
            
            if (posts.length === 0 && currentPage === 1) {
                postsFeed.innerHTML = `
                    <div class="empty-state">
                        <h3>No posts yet</h3>
                        <p>Be the first to share something!</p>
                    </div>
                `;
            } else {
                // Remove loading spinner if exists
                const spinner = postsFeed.querySelector('.loading-spinner');
                if (spinner) spinner.remove();
                
                // Render posts (no animation on load for better performance)
                posts.forEach((post) => {
                    const postElement = createPostElement(post);
                    postsFeed.appendChild(postElement);
                });
                
                // Update pagination
                hasMore = pagination.has_more;
                
                if (hasMore) {
                    loadMoreBtn.style.display = 'block';
                    document.getElementById('endOfFeed').style.display = 'none';
                } else {
                    loadMoreBtn.style.display = 'none';
                    if (posts.length > 0) {
                        document.getElementById('endOfFeed').style.display = 'block';
                    }
                }
            }
        } else {
            showFeedError('Failed to load posts');
        }
    } catch (error) {
        console.error('Load posts error:', error);
        showFeedError('Error loading posts');
    } finally {
        isLoading = false;
    }
}

/**
 * Initialize infinite scroll
 */
function initializeInfiniteScroll() {
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    
    loadMoreBtn.addEventListener('click', function() {
        if (hasMore && !isLoading) {
            currentPage++;
            loadPosts();
        }
    });
    
    // Optional: Auto-load on scroll
    window.addEventListener('scroll', function() {
        if (!hasMore || isLoading) return;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const pageHeight = document.documentElement.scrollHeight;
        
        // Load more when user is 300px from bottom
        if (scrollPosition >= pageHeight - 300) {
            currentPage++;
            loadPosts();
        }
    });
}

/**
 * Show create post message
 */
function showCreatePostMessage(text, type) {
    const messageDiv = document.getElementById('createPostMessage');
    messageDiv.textContent = text;
    messageDiv.className = 'message ' + type;
    messageDiv.style.display = 'block';
}

/**
 * Show feed error
 */
function showFeedError(message) {
    const postsFeed = document.getElementById('postsFeed');
    postsFeed.innerHTML = `
        <div class="empty-state">
            <h3>Error</h3>
            <p>${message}</p>
            <button onclick="location.reload()" style="margin-top: 10px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                Reload Page
            </button>
        </div>
    `;
}

/**
 * Format time ago
 */
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    const intervals = {
        year: 31536000,
        month: 2592000,
        week: 604800,
        day: 86400,
        hour: 3600,
        minute: 60
    };
    
    for (const [name, value] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / value);
        if (interval >= 1) {
            return interval === 1 ? `1 ${name} ago` : `${interval} ${name}s ago`;
        }
    }
    
    return 'Just now';
}