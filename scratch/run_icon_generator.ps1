Add-Type -Path "scratch/IconGenerator.cs" -ReferencedAssemblies "System.Drawing"

$ico16 = [IconGenerator]::CreateIcon(16)
$ico32 = [IconGenerator]::CreateIcon(32)
$ico48 = [IconGenerator]::CreateIcon(48)
$ico64 = [IconGenerator]::CreateIcon(64)
$ico180 = [IconGenerator]::CreateIcon(180)

$ico16.Save("public/favicon-16x16.png", [System.Drawing.Imaging.ImageFormat]::Png)
$ico32.Save("public/favicon-32x32.png", [System.Drawing.Imaging.ImageFormat]::Png)
$ico180.Save("public/apple-touch-icon.png", [System.Drawing.Imaging.ImageFormat]::Png)

$bitmaps = @($ico16, $ico32, $ico48, $ico64)
[IconGenerator]::SaveIco("public/favicon.ico", $bitmaps)

Write-Output "Successfully created all icon assets in public/!"

