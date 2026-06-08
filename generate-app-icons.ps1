# ============================================================
#  Génère les icônes de l'application (PWA) : icon-192.png et icon-512.png
#  Reprend la marque RPN : fond sombre, grande étoile or, barre tricolore
#  panafricaine en bas, coins arrondis (squircle-friendly / maskable).
#
#  Usage :  powershell -ExecutionPolicy Bypass -File .\generate-app-icons.ps1
#  Les PNG produits sont commités/déployés ; ce script reste local.
# ============================================================
Add-Type -AssemblyName System.Drawing

function New-RpmIcon {
    param([int]$Size, [string]$Path)

    $bmp = New-Object System.Drawing.Bitmap($Size, $Size)
    $g   = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.InterpolationMode  = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode    = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality

    # Couleurs de la marque
    $noir  = [System.Drawing.Color]::FromArgb(20, 17, 15)    # #14110f
    $or    = [System.Drawing.Color]::FromArgb(244, 193, 75)  # #f4c14b
    $rouge = [System.Drawing.Color]::FromArgb(230, 57, 70)   # #e63946
    $vert  = [System.Drawing.Color]::FromArgb(42, 157, 74)   # #2a9d4a

    # Fond plein (les coins seront arrondis par le masque OS ; on garde un fond
    # plein sur toute la surface pour le "maskable" — pas de transparence).
    $bg = New-Object System.Drawing.SolidBrush($noir)
    $g.FillRectangle($bg, 0, 0, $Size, $Size)

    # --- Étoile or (centrée, légèrement haute pour laisser place à la barre) ---
    $cx = $Size / 2.0
    $cy = $Size * 0.45
    $rOuter = $Size * 0.30
    $rInner = $rOuter * 0.42
    $pts = New-Object System.Collections.Generic.List[System.Drawing.PointF]
    for ($i = 0; $i -lt 10; $i++) {
        $r = if ($i % 2 -eq 0) { $rOuter } else { $rInner }
        # -90° pour pointe en haut ; pas de 36° entre sommets
        $ang = (-90 + $i * 36) * [Math]::PI / 180.0
        $x = $cx + $r * [Math]::Cos($ang)
        $y = $cy + $r * [Math]::Sin($ang)
        $pts.Add((New-Object System.Drawing.PointF([single]$x, [single]$y)))
    }
    $star = New-Object System.Drawing.SolidBrush($or)
    $g.FillPolygon($star, $pts.ToArray())

    # --- Barre tricolore panafricaine en bas ---
    $barH = [int]($Size * 0.18)
    $barY = $Size - $barH
    $third = $Size / 3.0
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($rouge)), 0, $barY, [int][Math]::Ceiling($third), $barH)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($noir)),  [int]$third, $barY, [int][Math]::Ceiling($third), $barH)
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($vert)),  [int]($third * 2), $barY, [int][Math]::Ceiling($third), $barH)

    $g.Dispose()
    $bmp.Save($Path, [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
    Write-Host "OK -> $Path ($Size x $Size)"
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
New-RpmIcon -Size 192 -Path (Join-Path $root 'icon-192.png')
New-RpmIcon -Size 512 -Path (Join-Path $root 'icon-512.png')
Write-Host "Icones generees."
