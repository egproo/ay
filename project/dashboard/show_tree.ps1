function Show-DirectoryTree {
    param (
        [string]$Path = (Get-Location),
        [string]$IndentChar = "|   ",
        [string]$Indent = ""
    )

    $items = Get-ChildItem -Path $Path
    $lastIndex = $items.Count - 1

    for ($i = 0; $i -lt $items.Count; $i++) {
        $item = $items[$i]
        $isLast = ($i -eq $lastIndex)
        
        if ($isLast) {
            Write-Output "$Indent`----$($item.Name)"
            $newIndent = "$Indent    "
        } else {
            Write-Output "$Indent|---$($item.Name)"
            $newIndent = "$Indent$IndentChar"
        }
        
        if ($item.PSIsContainer) {
            Show-DirectoryTree -Path $item.FullName -Indent $newIndent -IndentChar $IndentChar
        }
    }
}

Show-DirectoryTree | Out-File -FilePath .\project_structure.txt -Encoding utf8
