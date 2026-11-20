param(
  [string]$Base = "D:\xampp\htdocs\05092026\docs"
)

$ErrorActionPreference = "Stop"

$targets = @(
  @{ Name = "LAPORAN_PROYEK_AKHIR_RPL"; Toc = $true },
  @{ Name = "NASKAH_VIDEO_PROYEK_AKHIR"; Toc = $false }
)

$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0

foreach ($t in $targets) {
  $docx = Join-Path $Base ($t.Name + ".docx")
  $pdf = Join-Path $Base ($t.Name + ".pdf")
  $doc = $word.Documents.Open($docx, $false, $false)

  if ($t.Toc -and $doc.TablesOfContents.Count -gt 0) {
    $doc.TablesOfContents.Item(1).Update() | Out-Null
    $toc = $doc.TablesOfContents.Item(1)
    $toc.Range.ParagraphFormat.LineSpacingRule = 0
    $toc.Range.ParagraphFormat.SpaceAfter = 4
    $toc.Range.ParagraphFormat.SpaceBefore = 0
  }
  $doc.Save()
  $doc.Repaginate()

  $pages = $doc.ComputeStatistics(2)
  $doc.ExportAsFixedFormat($pdf, 17, $false, 0, 0)
  $doc.Close($false)
  Write-Output ("{0}.pdf -> {1} halaman" -f $t.Name, $pages)
}

$word.Quit()
[System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
