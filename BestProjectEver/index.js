// DOM Elements
const stage = document.querySelector('.stage');
const cutBtn = document.getElementById('cutBtn');
const resetBtn = document.getElementById('resetBtn');
const musicBtn = document.getElementById('musicBtn');
const slice = document.getElementById('slice');
const confettiCanvas = document.getElementById('confetti');
const popup = document.getElementById('wishPopup');
const closePopup = document.getElementById('closePopup');
const rightArm = document.querySelector('.arm.right');
const knife = document.querySelector('.knife');
const cake = document.querySelector('.cake');
const body = document.body;
const heartsContainer = document.getElementById('hearts');
const sparklesContainer = document.getElementById('sparkles');
const balloonContainer = document.getElementById('balloon-container');

// Audio
const birthdayMusic = new Audio('birthdaywish (mp3cut.net).mp3');
birthdayMusic.loop = true;
let isMusicPlaying = false;

// State
let isCutting = false;
let hasCutCake = false;

// Canvas setup
function setupCanvas() {
  confettiCanvas.width = confettiCanvas.clientWidth;
  confettiCanvas.height = confettiCanvas.clientHeight;
}

// Initialize
function init() {
  setupCanvas();
  createBalloons(5);
  setupEventListeners();
  spawnHearts();
  createSparkles();
}

// Event Listeners
function setupEventListeners() {
  window.addEventListener('resize', setupCanvas);
  
  // Cut Cake Button
  cutBtn.addEventListener('click', () => {
    if (!isCutting && !hasCutCake) {
      cutCake();
    }
  });
  
  // Reset Button
  resetBtn.addEventListener('click', resetCake);
  
  // Music Toggle
  musicBtn.addEventListener('click', toggleMusic);
  
  // Close Popup
  if (closePopup) {
    closePopup.addEventListener('click', () => {
      popup.classList.remove('active');
    });
  }
  
  // Click on cake slice
  slice.addEventListener('click', () => {
    if (!hasCutCake) {
      cutCake();
    }
  });
}

// Cake Cutting Animation
function cutCake() {
  if (isCutting || hasCutCake) return;
  
  isCutting = true;
  const rightArm = document.querySelector('.arm.right');
  const knife = document.querySelector('.knife');
  const cake = document.querySelector('.cake');
  
  // Add cutting class to trigger animation
  rightArm.classList.add('cutting');
  
  // Add sawing motion to the knife
  knife.style.animation = 'sawing 0.15s ease-in-out 3';
  
  // Play sound effect
  const cutSound = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-knife-chopping-vegetables-2461.mp3');
  cutSound.volume = 0.5;
  cutSound.play();
  
  // First cut animation
  setTimeout(() => {
    // Create cake crumbs effect
    const crumbs = document.createElement('div');
    crumbs.className = 'cake-crumbs';
    cake.appendChild(crumbs);
    
    // Show cake slice with a more dynamic animation
    const slice = document.querySelector('.slice');
    slice.style.opacity = '1';
    slice.style.transform = 'translateX(0)';
    
    // Add a small bounce effect with rotation
    setTimeout(() => {
      slice.style.transform = 'translateX(10px) rotate(-5deg)';
      // Add some visual feedback to the cake
      cake.style.transform = 'translateY(5px) scale(0.99)';
      
      setTimeout(() => {
        slice.style.transform = 'translateX(0) rotate(0)';
        cake.style.transform = 'translateY(0) scale(1)';
      }, 100);
    }, 200);
    
    // Complete the cutting action
    setTimeout(() => {
      // Final slice animation
      slice.classList.add('taken');
      hasCutCake = true;
      isCutting = false;
      
      // Remove sawing animation
      knife.style.animation = '';
      
      // Show birthday message with a slight delay
      setTimeout(() => showBirthdayMessage(), 300);
      
      // Trigger effects with staggered timing
      spawnConfetti();
      setTimeout(releaseBalloons, 500);
      
      // Play birthday music if not already playing
      if (!isMusicPlaying) {
        setTimeout(toggleMusic, 1000);
      }
      
      // Clean up crumbs after animation
      setTimeout(() => {
        if (cake.contains(crumbs)) {
          cake.removeChild(crumbs);
        }
      }, 2000);
      
    }, 600);
  }, 300);
}

// Reset Cake
function resetCake() {
  const rightArm = document.querySelector('.arm.right');
  const slice = document.querySelector('.slice');
  const popup = document.getElementById('wishPopup');
  
  // Reset animations and states
  rightArm.classList.remove('cutting');
  slice.classList.remove('taken');
  hasCutCake = false;
  
  // Reset slice position with animation
  slice.style.transition = 'all 0.5s ease';
  slice.style.opacity = '0';
  slice.style.transform = 'translateX(-100%)';
  
  // Reset arm and knife position with animation
  rightArm.style.transition = 'transform 0.5s ease';
  
  // Reset transitions after animation completes
  setTimeout(() => {
    slice.style.transition = '';
    rightArm.style.transition = '';
  }, 500);
  
  // Hide popup if visible
  if (popup) {
    popup.classList.remove('active');
  }
  
  // Stop and reset confetti
  if (window.confettiAnimation) {
    cancelAnimationFrame(window.confettiAnimation);
    window.confettiAnimation = null;
    const confettiCanvas = document.getElementById('confetti');
    const ctx = confettiCanvas.getContext('2d');
    ctx.clearRect(0, 0, confettiCanvas.width, confettiCanvas.height);
  }
}

// Toggle Music
function toggleMusic() {
  if (isMusicPlaying) {
    birthdayMusic.pause();
    musicBtn.innerHTML = '<i class="fas fa-play"></i> Play Music';
  } else {
    birthdayMusic.play().catch(e => console.log('Audio play failed:', e));
    musicBtn.innerHTML = '<i class="fas fa-pause"></i> Pause Music';
  }
  isMusicPlaying = !isMusicPlaying;
}

// Show Birthday Message
function showBirthdayMessage() {
  if (!popup) return;
  
  // Create message content if it doesn't exist
  if (!document.querySelector('.wish-lines')) {
    const messageContent = document.createElement('div');
    messageContent.className = 'wish-lines';
    messageContent.innerHTML = `
      <span>Happy birthday to you,</span>
      <span>Happy birthday to you,</span>
      <span>Happy birthday dear Sneha,</span>
      <span>Happy birthday to you! 🎶</span>
      <div style="margin-top: 20px; font-size: 1.5rem;">🎂🎉🎈</div>
    `;
    popup.querySelector('.popup-inner').appendChild(messageContent);
  }
  
  // Show popup with animation
  popup.classList.add('active');
}

// Create Balloons
function createBalloons(count) {
  if (!balloonContainer) return;
  
  const colors = ['#ff69b4', '#ffb6c1', '#ffc0cb', '#db7093', '#ff1493'];
  
  for (let i = 0; i < count; i++) {
    const balloon = document.createElement('div');
    balloon.className = 'balloon';
    balloon.style.left = `${10 + (i * 20)}%`;
    balloon.style.animationDelay = `${i * 2}s`;
    balloon.style.background = colors[i % colors.length];
    
    // Add string
    const string = document.createElement('div');
    string.className = 'string';
    balloon.appendChild(string);
    
    balloonContainer.appendChild(balloon);
  }
}

// Release Balloons
function releaseBalloons() {
  const balloons = document.querySelectorAll('.balloon');
  balloons.forEach((balloon, index) => {
    // Animate each balloon with a slight delay
    setTimeout(() => {
      balloon.style.animation = 'float-up 8s ease-out forwards';
      balloon.style.opacity = '0.9';
    }, index * 300);
  });
}

// Create Floating Hearts
function spawnHearts() {
  if (!heartsContainer) return;
  
  setInterval(() => {
    const heart = document.createElement('div');
    heart.className = 'heart';
    heart.style.left = `${Math.random() * 100}%`;
    heart.style.animationDuration = `${3 + Math.random() * 4}s`;
    heart.style.opacity = '0';
    
    heartsContainer.appendChild(heart);
    
    // Remove heart after animation
    setTimeout(() => {
      heart.remove();
    }, 7000);
  }, 800);
}

// Create Sparkles
function createSparkles() {
  if (!sparklesContainer) return;
  
  for (let i = 0; i < 20; i++) {
    const spark = document.createElement('div');
    spark.className = 'spark';
    spark.style.left = `${Math.random() * 100}%`;
    spark.style.top = `${Math.random() * 100}%`;
    spark.style.animationDelay = `${Math.random() * 2}s`;
    spark.style.animationDuration = `${1 + Math.random() * 2}s`;
    sparklesContainer.appendChild(spark);
  }
}

// Confetti Effect
let confettiPieces = [];
function spawnConfetti() {
  confettiPieces = [];
  const w = confettiCanvas.width, h = confettiCanvas.height;
  
  for (let i = 0; i < 140; i++) {
    confettiPieces.push({
      x: Math.random() * w,
      y: Math.random() * -h,
      r: 6 + Math.random() * 8,
      vx: -2 + Math.random() * 4,
      vy: 2 + Math.random() * 6,
      color: `hsl(${Math.floor(Math.random() * 360)}, 85%, 60%)`,
      rot: Math.random() * 360,
      vr: -6 + Math.random() * 12
    });
  }
  
  if (!window.confettiAnimation) {
    updateConfetti();
    window.confettiAnimation = true;
  }
}

function updateConfetti() {
  const ctx = confettiCanvas.getContext('2d');
  const w = confettiCanvas.width, h = confettiCanvas.height;
  
  // Clear canvas with slight transparency for trail effect
  ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
  ctx.fillRect(0, 0, w, h);
  
  let activePieces = 0;
  
  confettiPieces.forEach(p => {
    // Update position
    p.x += p.vx;
    p.y += p.vy;
    p.rot += p.vr * 0.1;
    
    // Reset if out of bounds
    if (p.y > h + 50 || p.x < -50 || p.x > w + 50) {
      p.x = Math.random() * w;
      p.y = -20;
      p.vy = 2 + Math.random() * 3;
    }
    
    // Draw confetti piece
    ctx.save();
    ctx.translate(p.x, p.y);
    ctx.rotate(p.rot * Math.PI / 180);
    
    // Random shape (square or circle)
    if (Math.random() > 0.3) {
      // Square
      ctx.fillStyle = p.color;
      ctx.fillRect(-p.r/2, -p.r/2, p.r, p.r);
    } else {
      // Circle
      ctx.beginPath();
      ctx.arc(0, 0, p.r/2, 0, Math.PI * 2);
      ctx.fillStyle = p.color;
      ctx.fill();
    }
    
    ctx.restore();
    activePieces++;
  });
  
  // Continue animation if there are active pieces
  if (activePieces > 0) {
    requestAnimationFrame(updateConfetti);
  } else {
    window.confettiAnimation = false;
  }
}

// Initialize the app
window.addEventListener('DOMContentLoaded', init);