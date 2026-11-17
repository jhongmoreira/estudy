// Gera as imagens de abertura (splash) do PWA para iPhone/iPad e o partial Blade com as tags.
// O iOS não monta o splash pelo manifest: exige uma imagem por tamanho de tela.
// Rodar: node scripts/splash.mjs
import sharp from 'sharp';
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';

// [largura CSS, altura CSS, pixel ratio, aparelhos]
const devices = [
    [440, 956, 3, 'iPhone 16 Pro Max'],
    [402, 874, 3, 'iPhone 16 Pro'],
    [430, 932, 3, 'iPhone 14/15 Pro Max, 15/16 Plus'],
    [393, 852, 3, 'iPhone 14/15 Pro, 15, 16'],
    [428, 926, 3, 'iPhone 12/13 Pro Max, 14 Plus'],
    [390, 844, 3, 'iPhone 12/13/14, 12/13 Pro'],
    [375, 812, 3, 'iPhone X/XS/11 Pro, 12/13 mini'],
    [414, 896, 3, 'iPhone XS Max, 11 Pro Max'],
    [414, 896, 2, 'iPhone XR, 11'],
    [414, 736, 3, 'iPhone 6/7/8 Plus'],
    [375, 667, 2, 'iPhone 6/7/8, SE 2ª/3ª geração'],
    [320, 568, 2, 'iPhone SE 1ª geração'],
    [1024, 1366, 2, 'iPad Pro 12,9"'],
    [834, 1194, 2, 'iPad Pro 11"'],
    [820, 1180, 2, 'iPad Air 10,9"'],
    [810, 1080, 2, 'iPad 10,2"'],
    [768, 1024, 2, 'iPad mini / 9,7"'],
];

const outDir = 'public/icons/splash';
mkdirSync(outDir, { recursive: true });

function svg(w, h) {
    const icon = Math.round(Math.min(w, h) * 0.26);
    const x = Math.round((w - icon) / 2);
    const y = Math.round(h / 2 - icon * 0.75);
    const font = Math.round(icon * 0.3);
    const logo = readFileSync('resources/icons/logo.svg', 'utf8')
        .replace('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">', '')
        .replace('</svg>', '');

    return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">
  <rect width="${w}" height="${h}" fill="#3047c9"/>
  <g transform="translate(${x} ${y}) scale(${icon / 512})">${logo}</g>
  <text x="${w / 2}" y="${y + icon + font * 1.6}" text-anchor="middle" fill="#ffffff"
        font-family="DejaVu Sans, Ubuntu, sans-serif" font-weight="700" font-size="${font}">Estudy</text>
</svg>`;
}

const links = [];
for (const [cw, ch, dpr, name] of devices) {
    const w = cw * dpr;
    const h = ch * dpr;
    const file = `apple-splash-${w}x${h}.png`;
    await sharp(Buffer.from(svg(w, h))).png({ compressionLevel: 9, palette: true }).toFile(`${outDir}/${file}`);
    links.push(`{{-- ${name} --}}\n<link rel="apple-touch-startup-image" href="/icons/splash/${file}" media="(device-width: ${cw}px) and (device-height: ${ch}px) and (-webkit-device-pixel-ratio: ${dpr}) and (orientation: portrait)">`);
    console.log('✓', file, `(${name})`);
}

writeFileSync('resources/views/components/layouts/splash.blade.php',
    `{{-- Gerado por scripts/splash.mjs — imagens de abertura do PWA no iPhone/iPad --}}\n${links.join('\n')}\n`);
console.log('✓ resources/views/components/layouts/splash.blade.php');
