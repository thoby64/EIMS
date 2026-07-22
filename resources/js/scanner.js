import { BrowserMultiFormatReader } from '@zxing/browser';

const startButton = document.querySelector('[data-start-scanner]');
const stopButton = document.querySelector('[data-stop-scanner]');
const video = document.querySelector('[data-scanner-video]');
const status = document.querySelector('[data-scanner-status]');
const form = document.querySelector('[data-scan-form]');
const codeInput = document.querySelector('[data-scan-code]');
const placeholder = document.querySelector('[data-scanner-placeholder]');
let controls;
let submitted = false;

async function stopScanner() {
    controls?.stop();
    controls = undefined;
    video?.classList.add('hidden');
    placeholder?.classList.remove('hidden');
    stopButton?.classList.add('hidden');
    startButton?.classList.remove('hidden');
}

startButton?.addEventListener('click', async () => {
    submitted = false;
    status.textContent = 'Requesting camera access…';
    try {
        const reader = new BrowserMultiFormatReader(undefined, { delayBetweenScanAttempts: 250 });
        controls = await reader.decodeFromConstraints(
            { audio: false, video: { facingMode: { ideal: 'environment' } } },
            video,
            result => {
                if (! result || submitted) return;
                submitted = true;
                codeInput.value = result.getText();
                status.textContent = 'Code detected. Opening asset…';
                stopScanner();
                form.requestSubmit();
            },
        );
        video.classList.remove('hidden');
        placeholder.classList.add('hidden');
        startButton.classList.add('hidden');
        stopButton.classList.remove('hidden');
        status.textContent = 'Point the camera at an EIMS QR code or barcode.';
    } catch (error) {
        status.textContent = 'Camera access was unavailable. Check browser permission or enter the code manually.';
    }
});

stopButton?.addEventListener('click', () => {
    stopScanner();
    status.textContent = 'Scanner stopped.';
});

window.addEventListener('pagehide', stopScanner);
