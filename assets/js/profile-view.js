/**
 * Profile View JavaScript
 * Handles profile display and user posts
 */

let userPostsPage = 1;
let isLoadingUserPosts = false;
let hasMoreUserPosts = true;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Profile page loaded');
    
    loadProfile();
    initializeProfileTabs();
    initializeUserPostsLoading();
});

/**
 * Load profile data
 */
async function loadProfile() {
    try {
        const response = await fetch(`${API_BASE}/users/get-profile.php?user_id=${profileUserId}`);
        const data = await response.json();
        
        if (data.success) {
            const user = data.data.user;
            
            // Update profile display
            document.getElementById('profileAvatar').src = `${APP_URL}/assets/images/uploads/${user.profile_pic}`;
            document.getElementById('profileUsername').textContent = user.username;
            document.getElementById('profileEmail').textContent = user.email;
            document.getElementById('profileBio').textContent = user.bio || 'Not provided';
            document.getElementById('profileLocation').textContent = user.location || 'Not provided';
            document.getElementById('profilePhone').textContent = user.phone || 'Not provided';
            document.getElementById('profileDOB').textContent = user.date_of_birth || 'Not provided';
            
            // Format join date
            const joinDate = new Date(user.created_at);
            document.getElementById('profileJoined').textContent = joinDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // Set current user data for posts/comments
            window.currentUsername = user.username;
            window.currentUserAvatar = user.profile_pic;
            
            // Load post count
            await loadUserPostCount();
        } else {
            alert('Failed to load profile');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        alert('Error loading profile');
    }
}

/**
 * Load user post count
 */
async function loadUserPostCount() {
    try {
        const response = await fetch(`${API_BASE}/posts/get-user-post-count.php?user_id=${profileUserId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('postCount').textContent = data.data.post_count;
        }
    } catch (error) {
        console.error('Error loading post count:', error);
    }
}

/**
 * Initialize profile tabs
 */
function initializeProfileTabs() {
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Hide all tabs
            document.getElementById('aboutTab').style.display = 'none';
            document.getElementById('postsTab').style.display = 'none';
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').style.display = 'block';
            this.classList.add('active');
            
            // Load posts when Posts tab is clicked
            if (tabName === 'posts' && !document.getElementById('postsTab').dataset.loaded) {
                loadUserPosts();
                document.getElementById('postsTab').dataset.loaded = 'true';
            }
        });
    });
}

/**
 * Initialize user posts loading
 */
function initializeUserPostsLoading() {
    const loadMoreBtn = document.getElementById('loadMoreUserPostsBtn');
    
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            if (hasMoreUserPosts && !isLoadingUserPosts) {
                userPostsPage++;
                loadUserPosts();
            }
        });
    }
}

/**
 * Load user posts
 */
async function loadUserPosts(refresh = false) {
    if (isLoadingUserPosts) return;
    
    isLoadingUserPosts = true;
    const postsFeed = document.getElementById('userPostsFeed');
    const loadMoreBtn = document.getElementById('loadMoreUserPostsBtn');
    
    if (refresh) {
        userPostsPage = 1;
        postsFeed.innerHTML = '<div class="loading-spinner">Loading posts...</div>';
        hasMoreUserPosts = true;
    }
    
    try {
        const response = await fetch(
            `${API_BASE}/posts/get-user-posts.php?user_id=${profileUserId}&page=${userPostsPage}&limit=10`
        );
        
        const data = await response.json();
        
        if (data.success) {
            const posts = data.data.posts;
            const pagination = data.data.pagination;
            
            if (refresh) {
                postsFeed.innerHTML = '';
            }
            
            if (posts.length === 0 && userPostsPage === 1) {
                postsFeed.innerHTML = `
                    <div class="empty-state">
                        <h3>No posts yet</h3>
                        <p>${isOwnProfile ? "You haven't posted anything yet." : "This user hasn't posted anything yet."}</p>
                    </div>
                `;
            } else {
                // Remove loading spinner if exists
                const spinner = postsFeed.querySelector('.loading-spinner');
                if (spinner) spinner.remove();
                
                // Render posts (using the same createPostElement from posts.js)
                posts.forEach(post => {
                    postsFeed.appendChild(createPostElement(post));
                });
                
                // Update pagination
                hasMoreUserPosts = pagination.has_more;
                
                if (hasMoreUserPosts) {
                    loadMoreBtn.style.display = 'block';
                    document.getElementById('endOfUserPosts').style.display = 'none';
                } else {
                    loadMoreBtn.style.display = 'none';
                    if (posts.length > 0) {
                        document.getElementById('endOfUserPosts').style.display = 'block';
                    }
                }
            }
        } else {
            postsFeed.innerHTML = `
                <div class="empty-state">
                    <h3>Error</h3>
                    <p>Failed to load posts</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Load user posts error:', error);
        postsFeed.innerHTML = `
            <div class="empty-state">
                <h3>Error</h3>
                <p>Error loading posts</p>
            </div>
        `;
    } finally {
        isLoadingUserPosts = false;
    }
}

/**
 * Time ago function (if not already defined)
 */
if (typeof timeAgo === 'undefined') {
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
}

/**
 * Escape HTML function (if not already defined)
 */
if (typeof escapeHtml === 'undefined') {
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}