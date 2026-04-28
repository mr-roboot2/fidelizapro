# Ícones do PWA

Substitua `icon-192.png` e `icon-512.png` pelos ícones definitivos da sua marca.

Por enquanto há um SVG genérico em `icon.svg`. Para gerar os PNGs:

1. Acesse https://realfavicongenerator.net/ ou https://www.pwabuilder.com/imageGenerator
2. Faça upload do `icon.svg` (ou use o seu logo)
3. Baixe os ícones 192x192 e 512x512 e salve como `icon-192.png` e `icon-512.png` aqui nesta pasta

Ou use ImageMagick localmente:

```bash
magick convert -density 1024 -background none icon.svg -resize 192x192 icon-192.png
magick convert -density 1024 -background none icon.svg -resize 512x512 icon-512.png
```
