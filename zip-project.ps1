$source = Get-ChildItem -Recurse -File | Where-Object {
    $_.FullName -notmatch '\\node_modules\\' -and
    $_.FullName -notmatch '\\dist\\' -and
    $_.FullName -notmatch '\\build\\' -and
    $_.Extension -ne '.zip'
}

$temp = Join-Path $PWD "temp_zip_dir"

# Clean temp if exists
if (Test-Path $temp) {
    Remove-Item $temp -Recurse -Force
}

# Copy filtered files
foreach ($file in $source) {
    $dest = Join-Path $temp ($file.FullName.Substring($PWD.Path.Length))
    $destDir = Split-Path $dest

    if (!(Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }

    Copy-Item $file.FullName -Destination $dest
}

# Create zip
Compress-Archive -Path "$temp\*" -DestinationPath "project.zip"

# Cleanup
Remove-Item $temp -Recurse -Force