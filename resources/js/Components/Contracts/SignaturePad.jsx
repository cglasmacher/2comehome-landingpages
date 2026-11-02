import { useEffect, useRef, useState } from 'react';

export default function SignaturePad({ onChange, disabled }) {
    const canvas = useRef(null);
    const drawing = useRef(false);
    const hasInk = useRef(false);
    const [empty, setEmpty] = useState(true);
    useEffect(() => {
        const ctx = canvas.current.getContext('2d');
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#172741';
    }, []);
    const point = (event) => {
        const rect = canvas.current.getBoundingClientRect();
        return [(event.clientX - rect.left) * canvas.current.width / rect.width, (event.clientY - rect.top) * canvas.current.height / rect.height];
    };
    const start = (event) => {
        if (disabled) return;
        event.preventDefault();
        canvas.current.setPointerCapture(event.pointerId);
        drawing.current = true;
        hasInk.current = false;
        const ctx = canvas.current.getContext('2d');
        ctx.beginPath();
        ctx.moveTo(...point(event));
    };
    const move = (event) => {
        if (!drawing.current || disabled) return;
        event.preventDefault();
        const ctx = canvas.current.getContext('2d');
        ctx.lineTo(...point(event));
        ctx.stroke();
        hasInk.current = true;
    };
    const finish = () => {
        if (!drawing.current) return;
        drawing.current = false;
        if (hasInk.current) {
            setEmpty(false);
            onChange(canvas.current.toDataURL('image/png'));
        }
    };
    const clear = () => {
        canvas.current.getContext('2d').clearRect(0, 0, canvas.current.width, canvas.current.height);
        drawing.current = false;
        setEmpty(true);
        onChange('');
    };
    return <div>
        <p id="signature-help" className="mb-2 text-sm text-[#74776f]">Mit dem Finger, Stift oder der Maus unterschreiben.</p>
        <canvas ref={canvas} width={1000} height={300} aria-label="Unterschriftsfeld" aria-describedby="signature-help" onPointerDown={start} onPointerMove={move} onPointerUp={finish} onPointerCancel={finish} onLostPointerCapture={finish} className="block w-full touch-none rounded-lg border-2 border-dashed border-[#d8d5cd] bg-white" />
        <button disabled={disabled || empty} type="button" onClick={clear} className="mt-3 text-sm text-[#a24a3d] underline disabled:opacity-40">Unterschrift löschen</button>
    </div>;
}
