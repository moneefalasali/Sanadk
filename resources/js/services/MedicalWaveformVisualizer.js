/**
 * Medical Waveform Visualizer - Real-time ECG/EEG visualization
 * Mimics hospital monitor displays with smooth animations
 */

class MedicalWaveformVisualizer {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) {
            throw new Error(`Canvas with ID '${canvasId}' not found`);
        }

        this.ctx = this.canvas.getContext('2d');
        this.width = this.canvas.width;
        this.height = this.canvas.height;

        // Configuration
        this.options = {
            gridColor: '#00ff00',
            waveColor: '#00ff00',
            backgroundColor: '#0a0a0a',
            lineWidth: 2,
            gridSize: 10,
            speed: 2,
            scale: 1,
            ...options
        };

        // Data buffers
        this.waveformData = [];
        this.displayBuffer = [];
        this.scrollPosition = 0;
        this.maxDataPoints = Math.ceil(this.width / this.options.speed);

        // Animation
        this.animationId = null;
        this.isAnimating = false;

        // Initialize
        this.setupCanvas();
    }

    /**
     * Setup canvas and initial drawing
     */
    setupCanvas() {
        // Set canvas size
        this.canvas.width = this.canvas.offsetWidth;
        this.canvas.height = this.canvas.offsetHeight;
        this.width = this.canvas.width;
        this.height = this.canvas.height;

        // Draw initial grid
        this.drawGrid();
    }

    /**
     * Draw medical grid background
     */
    drawGrid() {
        // Background
        this.ctx.fillStyle = this.options.backgroundColor;
        this.ctx.fillRect(0, 0, this.width, this.height);

        // Grid
        this.ctx.strokeStyle = this.options.gridColor;
        this.ctx.lineWidth = 0.5;
        this.ctx.globalAlpha = 0.3;

        // Vertical lines
        for (let x = 0; x < this.width; x += this.options.gridSize) {
            this.ctx.beginPath();
            this.ctx.moveTo(x, 0);
            this.ctx.lineTo(x, this.height);
            this.ctx.stroke();
        }

        // Horizontal lines
        for (let y = 0; y < this.height; y += this.options.gridSize) {
            this.ctx.beginPath();
            this.ctx.moveTo(0, y);
            this.ctx.lineTo(this.width, y);
            this.ctx.stroke();
        }

        this.ctx.globalAlpha = 1.0;
    }

    /**
     * Add data point to waveform
     */
    addDataPoint(value) {
        this.lastHeartRate = value;

        // Normalize value to canvas height
        const normalized = this.normalizeValue(value);
        this.waveformData.push(normalized);

        // Keep buffer size manageable
        if (this.waveformData.length > this.maxDataPoints * 2) {
            this.waveformData.shift();
        }
    }

    /**
     * Normalize value to canvas coordinates
     */
    normalizeValue(value) {
        // Assuming value is between 0-255 or similar
        // Normalize to canvas height with center baseline
        const normalized = (value / 255) * (this.height * 0.4);
        return this.height / 2 - normalized;
    }

    /**
     * Draw waveform
     */
    drawWaveform() {
        if (this.waveformData.length < 2) {
            return;
        }

        this.ctx.strokeStyle = this.options.waveColor;
        this.ctx.lineWidth = this.options.lineWidth;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';

        // Calculate starting position
        const startIndex = Math.max(0, this.waveformData.length - this.maxDataPoints);
        const xStep = this.width / (this.maxDataPoints - 1);

        this.ctx.beginPath();

        for (let i = startIndex; i < this.waveformData.length; i++) {
            const x = (i - startIndex) * xStep;
            const y = this.waveformData[i];

            if (i === startIndex) {
                this.ctx.moveTo(x, y);
            } else {
                this.ctx.lineTo(x, y);
            }
        }

        this.ctx.stroke();
    }

    /**
     * Draw pulse indicator (blinking dot)
     */
    drawPulseIndicator(heartRate) {
        const pulseRadius = 8;
        const pulseX = this.width - 30;
        const pulseY = 30;

        // Calculate pulse intensity based on heart rate
        const pulseIntensity = (heartRate % 60) / 60;

        // Draw background circle
        this.ctx.fillStyle = 'rgba(0, 255, 0, 0.2)';
        this.ctx.beginPath();
        this.ctx.arc(pulseX, pulseY, pulseRadius + 5, 0, Math.PI * 2);
        this.ctx.fill();

        // Draw pulse circle
        this.ctx.fillStyle = `rgba(0, 255, 0, ${0.5 + pulseIntensity * 0.5})`;
        this.ctx.beginPath();
        this.ctx.arc(pulseX, pulseY, pulseRadius, 0, Math.PI * 2);
        this.ctx.fill();

        // Draw heart rate text
        this.ctx.fillStyle = this.options.waveColor;
        this.ctx.font = 'bold 14px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(`${Math.round(heartRate)} BPM`, pulseX, pulseY + 30);
    }

    /**
     * Draw ECG-style waveform with QRS complex
     */
    drawECGWaveform(heartRate) {
        if (this.waveformData.length < 2) {
            return;
        }

        // Draw baseline
        this.ctx.strokeStyle = this.options.waveColor;
        this.ctx.lineWidth = 1;
        this.ctx.globalAlpha = 0.5;
        this.ctx.beginPath();
        this.ctx.moveTo(0, this.height / 2);
        this.ctx.lineTo(this.width, this.height / 2);
        this.ctx.stroke();
        this.ctx.globalAlpha = 1.0;

        // Draw waveform
        this.drawWaveform();

        // Draw pulse indicator
        this.drawPulseIndicator(heartRate);
    }

    /**
     * Draw EEG-style waveform with multiple channels
     */
    drawEEGWaveform(channelData) {
        const channels = Object.keys(channelData);
        const channelHeight = this.height / channels.length;

        channels.forEach((channel, index) => {
            const value = channelData[channel];
            const yOffset = (index + 0.5) * channelHeight;

            // Draw channel label
            this.ctx.fillStyle = this.options.waveColor;
            this.ctx.font = '12px Arial';
            this.ctx.textAlign = 'left';
            this.ctx.fillText(channel, 10, yOffset - 10);

            // Draw channel baseline
            this.ctx.strokeStyle = this.options.gridColor;
            this.ctx.lineWidth = 1;
            this.ctx.globalAlpha = 0.3;
            this.ctx.beginPath();
            this.ctx.moveTo(0, yOffset);
            this.ctx.lineTo(this.width, yOffset);
            this.ctx.stroke();
            this.ctx.globalAlpha = 1.0;

            // Draw signal
            this.ctx.strokeStyle = this.options.waveColor;
            this.ctx.lineWidth = 2;
            this.ctx.beginPath();
            this.ctx.moveTo(0, yOffset);
            this.ctx.lineTo(this.width, yOffset - (value / 255) * (channelHeight * 0.3));
            this.ctx.stroke();
        });
    }

    /**
     * Animate waveform scrolling
     */
    animate() {
        // Clear canvas
        this.drawGrid();

        // Draw waveform
        this.drawWaveform();

        if (this.lastHeartRate !== null && this.lastHeartRate !== undefined) {
            this.drawPulseIndicator(this.lastHeartRate);
        }

        // Continue animation
        if (this.isAnimating) {
            this.animationId = requestAnimationFrame(() => this.animate());
        }
    }

    /**
     * Start animation
     */
    start() {
        if (!this.isAnimating) {
            this.isAnimating = true;
            this.animate();
        }
    }

    /**
     * Stop animation
     */
    stop() {
        this.isAnimating = false;
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
    }

    /**
     * Update with real-time data
     */
    update(data) {
        if (typeof data === 'number') {
            // Single value (heart rate)
            this.addDataPoint(data);
        } else if (typeof data === 'object') {
            // Multiple channels (EEG)
            this.drawEEGWaveform(data);
        }
    }

    /**
     * Clear waveform data
     */
    clear() {
        this.waveformData = [];
        this.drawGrid();
    }

    /**
     * Set waveform color
     */
    setWaveColor(color) {
        this.options.waveColor = color;
    }

    /**
     * Set animation speed
     */
    setSpeed(speed) {
        this.options.speed = speed;
        this.maxDataPoints = Math.ceil(this.width / speed);
    }

    /**
     * Get current waveform data
     */
    getWaveformData() {
        return [...this.waveformData];
    }

    /**
     * Export waveform as image
     */
    exportAsImage() {
        return this.canvas.toDataURL('image/png');
    }

    /**
     * Resize canvas
     */
    resize() {
        this.setupCanvas();
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MedicalWaveformVisualizer;
}
