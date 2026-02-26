/**
 * Comments JavaScript
 * Handles real-time commenting
 */

/**
 * Toggle comments section
 */
async function toggleComments(postId) {
    const commentsSection = document.getElementById(`commentsSection${postId}`);
    
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        // Load comments if not already loaded
        if (!commentsSection.dataset.loaded) {
            await loadComments(postId);
            commentsSection.dataset.loaded = 'true';
        }
        // Focus on input
        document.getElementById(`commentInput${postId}`).focus();
    } else {
        commentsSection.style.display = 'none';
    }
}

/**
 * Load comments for a post
 */
async function loadComments(postId) {
    try {
        const response = await fetch(
            `${window.API_BASE}/comments/get-comments.php?post_id=${postId}`
        );
        
        const data = await response.json();
        
        if (data.success) {
            const commentsList = document.getElementById(`commentsList${postId}`);
            commentsList.innerHTML = '';
            
            data.data.comments.forEach(comment => {
                commentsList.appendChild(createCommentElement(comment));
            });
        }
    } catch (error) {
        console.error('Load comments error:', error);
    }
}

/**
 * Create comment element
 */
function createCommentElement(comment) {
    const commentItem = document.createElement('div');
    commentItem.className = 'comment-item';
    commentItem.dataset.commentId = comment.comment_id;
    
    commentItem.innerHTML = `
        <img src="${window.APP_URL}/assets/images/uploads/${comment.profile_pic}" 
             alt="${comment.username}" 
             class="comment-avatar">
        <div class="comment-content-wrapper">
            <div class="comment-username">${escapeHtml(comment.username)}</div>
            <div class="comment-text">${escapeHtml(comment.content)}</div>
            <div class="comment-time">${timeAgo(comment.created_at)}</div>
        </div>
    `;
    
    return commentItem;
}

/**
 * Handle comment key press (Enter to submit)
 */
function handleCommentKeyPress(event, postId) {
    if (event.key === 'Enter') {
        event.preventDefault();
        addComment(postId);
    }
}

/**
 * Add comment (WITH ANIMATION)
 */
async function addComment(postId) {
    const input = document.getElementById(`commentInput${postId}`);
    const content = input.value.trim();
    
    if (!content) {
        return;
    }
    
    if (content.length > 1000) {
        alert('Comment is too long (max 1000 characters)');
        return;
    }
    
    try {
        const response = await fetch(window.API_BASE + '/comments/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                post_id: postId,
                content: content
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Clear input
            input.value = '';
            
            // Add comment to list
            const commentsList = document.getElementById(`commentsList${postId}`);
            const commentElement = createCommentElement(data.data.comment);
            commentsList.appendChild(commentElement);
            
            // Animate new comment
            if (typeof animateNewComment === 'function') {
                animateNewComment(commentElement);
            }
            
            // Update comment count
            const currentCount = parseInt(document.getElementById(`commentCount${postId}`).textContent);
            document.getElementById(`commentCount${postId}`).textContent = 
                `${currentCount + 1} comments`;
        } else {
            alert(data.message || 'Failed to add comment');
        }
    } catch (error) {
        console.error('Add comment error:', error);
        alert('Error adding comment');
    }
}