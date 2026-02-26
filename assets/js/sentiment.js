/**
 * Sentiment Analysis with TensorFlow.js
 * Analyzes post content for positive/negative/neutral sentiment
 */

let sentimentModel = null;
let isModelLoading = false;

/**
 * Initialize sentiment analysis (lightweight approach)
 */
async function initializeSentiment() {
    console.log('Sentiment analysis ready (using simple keyword-based approach)');
    // We'll use a simple keyword-based approach that works instantly
    // TensorFlow models are large and can be slow to load
}

/**
 * Analyze sentiment of text (Simple keyword-based)
 * Returns a score between 0 (negative) and 1 (positive)
 */
function analyzeSentiment(text) {
    if (!text || text.trim().length === 0) {
        return 0.5; // Neutral for empty text
    }
    
    const lowerText = text.toLowerCase();
    
    // Positive keywords
    const positiveWords = [
        'love', 'great', 'amazing', 'awesome', 'excellent', 'wonderful', 'fantastic',
        'happy', 'joy', 'beautiful', 'perfect', 'best', 'good', 'nice', 'super',
        'brilliant', 'excited', 'thank', 'thanks', 'appreciate', 'like', 'enjoy',
        'lovely', 'glad', 'delighted', 'pleased', 'satisfied', 'impressed'
    ];
    
    // Negative keywords
    const negativeWords = [
        'hate', 'bad', 'terrible', 'awful', 'horrible', 'worst', 'poor', 'sad',
        'angry', 'disappointed', 'disgusting', 'annoying', 'annoyed', 'frustrating',
        'frustrated', 'upset', 'unhappy', 'dislike', 'boring', 'useless', 'waste',
        'terrible', 'pathetic', 'ridiculous', 'stupid', 'sucks', 'fail', 'failed'
    ];
    
    // Very positive phrases
    const veryPositiveWords = [
        'absolutely love', 'so happy', 'very good', 'really great', 'so excited',
        'super happy', 'extremely happy', 'love it', 'loved it'
    ];
    
    // Very negative phrases
    const veryNegativeWords = [
        'absolutely hate', 'so sad', 'very bad', 'really terrible', 'so disappointed',
        'super angry', 'extremely upset', 'hate it', 'hated it'
    ];
    
    let score = 0.5; // Start neutral
    
    // Check for very strong phrases first (worth more points)
    veryPositiveWords.forEach(phrase => {
        if (lowerText.includes(phrase)) {
            score += 0.15;
        }
    });
    
    veryNegativeWords.forEach(phrase => {
        if (lowerText.includes(phrase)) {
            score -= 0.15;
        }
    });
    
    // Count positive words
    let positiveCount = 0;
    positiveWords.forEach(word => {
        const regex = new RegExp('\\b' + word + '\\b', 'gi');
        const matches = lowerText.match(regex);
        if (matches) {
            positiveCount += matches.length;
        }
    });
    
    // Count negative words
    let negativeCount = 0;
    negativeWords.forEach(word => {
        const regex = new RegExp('\\b' + word + '\\b', 'gi');
        const matches = lowerText.match(regex);
        if (matches) {
            negativeCount += matches.length;
        }
    });
    
    // Calculate score adjustment
    const totalWords = lowerText.split(/\s+/).length;
    const positiveRatio = positiveCount / Math.max(totalWords, 1);
    const negativeRatio = negativeCount / Math.max(totalWords, 1);
    
    // Adjust score based on ratios
    score += positiveRatio * 0.5;
    score -= negativeRatio * 0.5;
    
    // Check for exclamation marks (enthusiasm)
    const exclamationCount = (text.match(/!/g) || []).length;
    if (exclamationCount > 0 && positiveCount > negativeCount) {
        score += Math.min(exclamationCount * 0.05, 0.15);
    }
    
    // Check for question marks (uncertainty/concern)
    const questionCount = (text.match(/\?/g) || []).length;
    if (questionCount > 2) {
        score -= 0.05;
    }
    
    // Check for ALL CAPS (could be positive excitement or negative anger)
    const capsWords = text.match(/\b[A-Z]{3,}\b/g);
    if (capsWords && capsWords.length > 0) {
        if (positiveCount > negativeCount) {
            score += 0.1; // Excited
        } else if (negativeCount > positiveCount) {
            score -= 0.1; // Angry
        }
    }
    
    // Clamp score between 0 and 1
    score = Math.max(0, Math.min(1, score));
    
    return score;
}

/**
 * Get sentiment category from score
 */
function getSentimentCategory(score) {
    if (score >= 0.6) {
        return 'positive';
    } else if (score >= 0.3) {
        return 'neutral';
    } else {
        return 'negative';
    }
}

/**
 * Analyze post content and return sentiment info
 */
function analyzePostSentiment(content) {
    const score = analyzeSentiment(content);
    const category = getSentimentCategory(score);
    
    return {
        score: score,
        category: category,
        info: getSentimentInfo(score)
    };
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    initializeSentiment();
});