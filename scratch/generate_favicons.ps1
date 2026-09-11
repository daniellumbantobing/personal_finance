Add-Type -AssemblyName System.Drawing

function Create-FinAiIcon([int]$size) {
    $bmp = New-Object System.Drawing.Bitmap($size, $size, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.Clear([System.Drawing.Color]::Transparent)

    $scale = $size / 64.0

    # Squircle background path
    $rectX = 2 * $scale
    $rectY = 2 * $scale
    $rectW = 60 * $scale
    $rectH = 60 * $scale
    $radius = 18 * $scale

    $bgPath = New-Object System.Drawing.Drawing2D.GraphicsPath
    $bgPath.AddArc($rectX, $rectY, $radius * 2, $radius * 2, 180, 90)
    $bgPath.AddArc($rectX + $rectW - ($radius * 2), $rectY, $radius * 2, $radius * 2, 270, 90)
    $bgPath.AddArc($rectX + $rectW - ($radius * 2), $rectY + $rectH - ($radius * 2), $radius * 2, $radius * 2, 0, 90)
    $bgPath.AddArc($rectX, $rectY + $rectH - ($radius * 2), $radius * 2, $radius * 2, 90, 90)
    $bgPath.CloseFigure()

    # Background Gradient (Deep Slate to Electric Indigo)
    $cTopRight = [System.Drawing.Color]::FromArgb(255, 79, 70, 229)    # #4f46e5
    $cBottomLeft = [System.Drawing.Color]::FromArgb(255, 10, 15, 30)   # #0a0f1e
    $bgBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.PointF(0, $size)),
        (New-Object System.Drawing.PointF($size, 0)),
        $cBottomLeft,
        $cTopRight
    )
    $g.FillPath($bgBrush, $bgPath)
    $bgBrush.Dispose()

    # Outer border glow
    $borderPen = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(70, 129, 140, 248), 1.5 * $scale)
    $g.DrawPath($borderPen, $bgPath)
    $borderPen.Dispose()
    $bgPath.Dispose()

    # Draw Infinity Loop / FinAI Ribbon
    $infPath = New-Object System.Drawing.Drawing2D.GraphicsPath
    # Bezier points scaled
    $p1 = New-Object System.Drawing.PointF(21 * $scale, 23 * $scale)
    $c1 = New-Object System.Drawing.PointF(14 * $scale, 23 * $scale)
    $c2 = New-Object System.Drawing.PointF(10 * $scale, 27 * $scale)
    $p2 = New-Object System.Drawing.PointF(10 * $scale, 32 * $scale)
    $infPath.AddBezier($p1, $c1, $c2, $p2)

    $c3 = New-Object System.Drawing.PointF(10 * $scale, 37 * $scale)
    $c4 = New-Object System.Drawing.PointF(14 * $scale, 41 * $scale)
    $p3 = New-Object System.Drawing.PointF(21 * $scale, 41 * $scale)
    $infPath.AddBezier($p2, $c3, $c4, $p3)

    $c5 = New-Object System.Drawing.PointF(27.5 * $scale, 41 * $scale)
    $c6 = New-Object System.Drawing.PointF(30.5 * $scale, 37 * $scale)
    $p4 = New-Object System.Drawing.PointF(32 * $scale, 34.5 * $scale)
    $infPath.AddBezier($p3, $c5, $c6, $p4)

    $c7 = New-Object System.Drawing.PointF(33.5 * $scale, 37 * $scale)
    $c8 = New-Object System.Drawing.PointF(36.5 * $scale, 41 * $scale)
    $p5 = New-Object System.Drawing.PointF(43 * $scale, 41 * $scale)
    $infPath.AddBezier($p4, $c7, $c8, $p5)

    $c9 = New-Object System.Drawing.PointF(50 * $scale, 41 * $scale)
    $c10 = New-Object System.Drawing.PointF(54 * $scale, 37 * $scale)
    $p6 = New-Object System.Drawing.PointF(54 * $scale, 32 * $scale)
    $infPath.AddBezier($p5, $c9, $c10, $p6)

    $c11 = New-Object System.Drawing.PointF(54 * $scale, 27 * $scale)
    $c12 = New-Object System.Drawing.PointF(50 * $scale, 23 * $scale)
    $p7 = New-Object System.Drawing.PointF(43 * $scale, 23 * $scale)
    $infPath.AddBezier($p6, $c11, $c12, $p7)

    $c13 = New-Object System.Drawing.PointF(36.5 * $scale, 23 * $scale)
    $c14 = New-Object System.Drawing.PointF(33.5 * $scale, 27 * $scale)
    $p8 = New-Object System.Drawing.PointF(32 * $scale, 29.5 * $scale)
    $infPath.AddBezier($p7, $c13, $c14, $p8)

    $c15 = New-Object System.Drawing.PointF(30.5 * $scale, 27 * $scale)
    $c16 = New-Object System.Drawing.PointF(27.5 * $scale, 23 * $scale)
    $infPath.AddBezier($p8, $c15, $c16, $p1)

    $strokeGrad = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
        (New-Object System.Drawing.PointF(10 * $scale, 20 * $scale)),
        (New-Object System.Drawing.PointF(54 * $scale, 44 * $scale)),
        [System.Drawing.Color]::FromArgb(255, 56, 189, 248),    # Sky blue
        [System.Drawing.Color]::FromArgb(255, 52, 211, 153)     # Emerald
    )
    $infPen = New-Object System.Drawing.Pen($strokeGrad, [Math]::Max(2, 6.5 * $scale))
    $infPen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $infPen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $infPen.LineJoin = [System.Drawing.Drawing2D.LineJoin]::Round
    $g.DrawPath($infPen, $infPath)
    $infPen.Dispose()
    $strokeGrad.Dispose()
    $infPath.Dispose()

    # Upward Green Growth Spark
    $sparkX = 43 * $scale
    $sparkY = 23 * $scale
    $rOuter = [Math]::Max(1.5, 3.5 * $scale)
    $rInner = [Math]::Max(1.0, 1.6 * $scale)

    $sparkOuterBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(255, 52, 211, 153))
    $g.FillEllipse($sparkOuterBrush, $sparkX - $rOuter, $sparkY - $rOuter, $rOuter * 2, $rOuter * 2)
    $sparkOuterBrush.Dispose()

    $sparkInnerBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::White)
    $g.FillEllipse($sparkInnerBrush, $sparkX - $rInner, $sparkY - $rInner, $rInner * 2, $rInner * 2)
    $sparkInnerBrush.Dispose()

    $g.Dispose()
    return $bmp
}

# 1. Generate PNGs
$bmp64 = Create-FinAiIcon 64
$bmp64.Save("public/favicon-64.png", [System.Drawing.Imaging.ImageFormat]::Png)

$bmp32 = Create-FinAiIcon 32
$bmp32.Save("public/favicon-32.png", [System.Drawing.Imaging.ImageFormat]::Png)

$bmp16 = Create-FinAiIcon 16
$bmp16.Save("public/favicon-16.png", [System.Drawing.Imaging.ImageFormat]::Png)

$bmp180 = Create-FinAiIcon 180
$bmp180.Save("public/apple-touch-icon.png", [System.Drawing.Imaging.ImageFormat]::Png)

# 2. Package into multi-resolution favicon.ico (containing PNGs for 16, 32, 64)
$pngBytesList = @(
    [System.IO.File]::ReadAllBytes("public/favicon-16.png"),
    [System.IO.File]::ReadAllBytes("public/favicon-32.png"),
    [System.IO.File]::ReadAllBytes("public/favicon-64.png")
)
$sizes = @(16, 32, 64)

$ms = New-Object System.IO.MemoryStream
$bw = New-Object System.IO.BinaryWriter($ms)

# Header: reserved (0), type (1 = icon), count (3)
$bw.Write([uint16]0)
$bw.Write([uint16]1)
$bw.Write([uint16]3)

$offset = 6 + (16 * 3) # Header + (3 * 16 bytes per entry)

for ($i = 0; $i -lt 3; $i++) {
    $s = $sizes[$i]
    $data = $pngBytesList[$i]
    
    $bw.Write([byte]$s)        # Width
    $bw.Write([byte]$s)        # Height
    $bw.Write([byte]0)         # Colors
    $bw.Write([byte]0)         # Reserved
    $bw.Write([uint16]1)       # Planes
    $bw.Write([uint16]32)      # Bit count
    $bw.Write([uint32]$data.Length) # Bytes in resource
    $bw.Write([uint32]$offset)      # Offset
    
    $offset += $data.Length
}

for ($i = 0; $i -lt 3; $i++) {
    $bw.Write($pngBytesList[$i])
}

$bw.Flush()
[System.IO.File]::WriteAllBytes("public/favicon.ico", $ms.ToArray())
$bw.Close()
$ms.Close()

Write-Output "Favicons successfully generated!"

