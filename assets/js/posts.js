/**
 * Posts Interactions JavaScript
 * Handles likes, dislikes, and post actions
 */

/**
 * Create post element (ENHANCED WITH RATINGS)
 */
function createPostElement(post) {
    const postCard = document.createElement('div');
    postCard.className = 'post-card';
    postCard.dataset.postId = post.post_id;
    
    // Build media HTML
    let mediaHTML = '';
    if (post.media_type === 'image' && post.media_url) {
        mediaHTML = `
            <div class="post-media">
                <img src="${window.APP_URL}/assets/images/uploads/${post.media_url}" alt="Post image">
            </div>
        `;
    } else if (post.media_type === 'youtube' && post.youtube_embed) {
        mediaHTML = `
            <div class="post-media">
                <iframe src="https://www.youtube.com/embed/${post.youtube_embed}" allowfullscreen></iframe>
            </div>
        `;
    }
    
    // Build menu HTML (only for post owner)
    let menuHTML = '';
    if (post.is_owner) {
        menuHTML = `
            <div class="post-menu">
                <button class="post-menu-btn" onclick="togglePostMenu(${post.post_id})">⋮</button>
                <div id="postMenu${post.post_id}" class="post-dropdown">
                    <button onclick="deletePost(${post.post_id})">🗑️ Delete Post</button>
                </div>
            </div>
        `;
    }
    
    // Determine like button states
    const likeClass = post.user_like_status === 'like' ? 'liked' : '';
    const dislikeClass = post.user_like_status === 'dislike' ? 'disliked' : '';
    
    // Build sentiment indicator
    let sentimentHTML = '';
    if (post.sentiment_score !== null && post.sentiment_score !== undefined) {
        const sentiment = getSentimentInfo(post.sentiment_score);
        sentimentHTML = `
            <div class="sentiment-indicator" title="Sentiment: ${sentiment.label}">
                <span class="sentiment-emoji">${sentiment.emoji}</span>
                <span class="sentiment-label">${sentiment.label}</span>
            </div>
        `;
    }
    
    // Build rating stars
    const ratingStars = buildRatingStars(post.average_rating || 0, post.post_id);
    const ratingText = post.rating_count > 0 
        ? `${post.average_rating}/5 (${post.rating_count} ${post.rating_count === 1 ? 'rating' : 'ratings'})`
        : 'No ratings yet';
    
    postCard.innerHTML = `
        <div class="post-header">
            <img src="${window.APP_URL}/assets/images/uploads/${post.profile_pic}" 
                 alt="${post.username}" 
                 class="post-avatar">
            <div class="post-user-info">
                <a href="profile.php?user_id=${post.user_id}" class="post-username">${post.username}</a>
                <div class="post-time">${timeAgo(post.created_at)}</div>
            </div>
            ${menuHTML}
        </div>
        
        <div class="post-content">${escapeHtml(post.content)}</div>
        
        ${sentimentHTML}
        ${mediaHTML}
        
        <div class="post-stats">
            <span id="likeCount${post.post_id}">${post.like_count} likes</span>
            <span id="dislikeCount${post.post_id}">${post.dislike_count} dislikes</span>
            <span id="commentCount${post.post_id}">${post.comment_count} comments</span>
        </div>
        
        <div class="post-rating-section">
            <div class="rating-stars" id="ratingStars${post.post_id}">
                ${ratingStars}
            </div>
            <div class="rating-text" id="ratingText${post.post_id}">${ratingText}</div>
        </div>
        
        <div class="post-interactions">
            <button class="interaction-btn ${likeClass}" onclick="toggleLike(${post.post_id}, 'like')">
                <span>👍</span>
                <span>Like</span>
            </button>
            <button class="interaction-btn ${dislikeClass}" onclick="toggleLike(${post.post_id}, 'dislike')">
                <span>👎</span>
                <span>Dislike</span>
            </button>
            <button class="interaction-btn" onclick="toggleComments(${post.post_id})">
                <span>💬</span>
                <span>Comment</span>
            </button>
        </div>
        
        <div id="commentsSection${post.post_id}" class="comments-section" style="display: none;">
            <div class="comment-input-box">
                <img src="${window.APP_URL}/assets/images/uploads/${window.currentUserAvatar}" alt="You">
                <input 
                    type="text" 
                    id="commentInput${post.post_id}" 
                    placeholder="Write a comment..."
                    onkeypress="handleCommentKeyPress(event, ${post.post_id})"
                >
            </div>
            <div id="commentsList${post.post_id}" class="comments-list"></div>
        </div>
    `;
    
    return postCard;
}

/**
 * Build rating stars HTML
 */
function buildRatingStars(averageRating, postId) {
    let starsHTML = '';
    
    for (let i = 1; i <= 5; i++) {
        const filled = i <= Math.round(averageRating);
        const starClass = filled ? 'star-filled' : 'star-empty';
        starsHTML += `<span class="star ${starClass}" onclick="ratePost(${postId}, ${i})">★</span>`;
    }
    
    return starsHTML;
}

/**
 * Rate a post
 */
async function ratePost(postId, rating) {
    try {
        const response = await fetch(window.API_BASE + '/ratings/submit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                rating: rating
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update rating display
            const ratingStars = buildRatingStars(data.data.average_rating, postId);
            document.getElementById(`ratingStars${postId}`).innerHTML = ratingStars;
            
            const ratingText = `${data.data.average_rating}/5 (${data.data.total_ratings} ${data.data.total_ratings === 1 ? 'rating' : 'ratings'})`;
            document.getElementById(`ratingText${postId}`).textContent = ratingText;
            
            // Add animation
            const ratingSection = document.querySelector(`[data-post-id="${postId}"] .post-rating-section`);
            ratingSection.classList.add('rating-updated');
            setTimeout(() => {
                ratingSection.classList.remove('rating-updated');
            }, 500);
        }
    } catch (error) {
        console.error('Rating error:', error);
    }
}

/**
 * Get sentiment information
 */
function getSentimentInfo(score) {
    if (score >= 0.6) {
        return { emoji: '😊', label: 'Positive', color: '#10b981' };
    } else if (score >= 0.3) {
        return { emoji: '😐', label: 'Neutral', color: '#6b7280' };
    } else {
        return { emoji: '😔', label: 'Negative', color: '#ef4444' };
    }
}

/**
 * Toggle like/dislike (WITH ANIMATION)
 */
async function toggleLike(postId, likeType) {
    try {
        const response = await fetch(window.API_BASE + '/likes/toggle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                like_type: likeType
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update counts
            document.getElementById(`likeCount${postId}`).textContent = 
                `${data.data.counts.like_count} likes`;
            document.getElementById(`dislikeCount${postId}`).textContent = 
                `${data.data.counts.dislike_count} dislikes`;
            
            // Update button states
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            const likeBtn = postCard.querySelector('.interaction-btn:nth-child(1)');
            const dislikeBtn = postCard.querySelector('.interaction-btn:nth-child(2)');
            
            // Animate the clicked button
            const clickedBtn = likeType === 'like' ? likeBtn : dislikeBtn;
            if (typeof animateLike === 'function') {
                animateLike(clickedBtn);
            }
            
            // Remove all active states
            likeBtn.classList.remove('liked');
            dislikeBtn.classList.remove('disliked');
            
            // Add active state if not removed
            if (data.data.action !== 'removed') {
                if (data.data.like_type === 'like') {
                    likeBtn.classList.add('liked');
                } else if (data.data.like_type === 'dislike') {
                    dislikeBtn.classList.add('disliked');
                }
            }
        }
    } catch (error) {
        console.error('Like toggle error:', error);
    }
}

/**
 * Delete post (WITH ANIMATION)
 */
async function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) {
        return;
    }
    
    try {
        const response = await fetch(window.API_BASE + '/posts/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ post_id: postId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Animate and remove post from DOM
            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            
            if (typeof animatePostDeletion === 'function') {
                await animatePostDeletion(postCard);
            } else {
                postCard.remove();
            }
        } else {
            alert(data.message || 'Failed to delete post');
        }
    } catch (error) {
        console.error('Delete post error:', error);
        alert('Error deleting post');
    }
}

/**
 * Rate a post (WITH ANIMATION)
 */
async function ratePost(postId, rating) {
    try {
        const response = await fetch(window.API_BASE + '/ratings/submit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                rating: rating
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update rating display
            const ratingStars = buildRatingStars(data.data.average_rating, postId);
            const starsContainer = document.getElementById(`ratingStars${postId}`);
            starsContainer.innerHTML = ratingStars;
            
            const ratingText = `${data.data.average_rating}/5 (${data.data.total_ratings} ${data.data.total_ratings === 1 ? 'rating' : 'ratings'})`;
            document.getElementById(`ratingText${postId}`).textContent = ratingText;
            
            // Animate rating stars
            if (typeof animateRating === 'function') {
                animateRating(starsContainer);
            }
            
            // Add animation to rating section
            const ratingSection = document.querySelector(`[data-post-id="${postId}"] .post-rating-section`);
            ratingSection.classList.add('rating-updated');
            setTimeout(() => {
                ratingSection.classList.remove('rating-updated');
            }, 500);
        }
    } catch (error) {
        console.error('Rating error:', error);
    }
}

/**
 * Toggle post menu
 */
function togglePostMenu(postId) {
    const menu = document.getElementById(`postMenu${postId}`);
    menu.classList.toggle('show');
    
    // Close menu when clicking outside
    document.addEventListener('click', function closeMenu(e) {
        if (!e.target.closest('.post-menu')) {
            menu.classList.remove('show');
            document.removeEventListener('click', closeMenu);
        }
    });
}

/**
 * Delete post (WITH ANIMATION - FIXED)
 */
async function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post?')) {
        return;
    }
    
    const postCard = document.querySelector(`[data-post-id="${postId}"]`);
    
    if (!postCard) {
        console.error('Post card not found');
        return;
    }
    
    // First animate the deletion
    console.log('Starting delete animation for post:', postId);
    
    if (typeof gsap !== 'undefined') {
        // Animate with GSAP
        await new Promise((resolve) => {
            gsap.to(postCard, {
                duration: 0.6,
                opacity: 0,
                x: 150,
                scale: 0.8,
                ease: 'power2.in',
                onComplete: resolve
            });
        });
    } else {
        // CSS fallback
        postCard.style.transition = 'all 0.6s ease-in';
        postCard.style.opacity = '0';
        postCard.style.transform = 'translateX(150px) scale(0.8)';
        await new Promise(resolve => setTimeout(resolve, 600));
    }
    
    // Then delete from backend
    try {
        const response = await fetch(window.API_BASE + '/posts/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ post_id: postId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Post deleted successfully');
            // Remove from DOM
            postCard.remove();
        } else {
            console.error('Failed to delete from backend:', data.message);
            // Restore the post card if backend fails
            if (typeof gsap !== 'undefined') {
                gsap.to(postCard, {
                    duration: 0.3,
                    opacity: 1,
                    x: 0,
                    scale: 1
                });
            } else {
                postCard.style.opacity = '1';
                postCard.style.transform = 'translateX(0) scale(1)';
            }
            alert(data.message || 'Failed to delete post');
        }
    } catch (error) {
        console.error('Delete post error:', error);
        // Restore the post card on error
        if (typeof gsap !== 'undefined') {
            gsap.to(postCard, {
                duration: 0.3,
                opacity: 1,
                x: 0,
                scale: 1
            });
        } else {
            postCard.style.opacity = '1';
            postCard.style.transform = 'translateX(0) scale(1)';
        }
        alert('Error deleting post');
    }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}