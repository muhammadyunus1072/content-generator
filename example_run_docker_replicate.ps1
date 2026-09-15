$body = @{
    input = @{
        prompt = "a woman with long hair on the edge of a river with lots of fish and a strong current, full width camera from a distance"
        width = 512
        height = 512
        num_outputs = 1
        guidance_scale = 7
        num_inference_steps = 20
    }
} | ConvertTo-Json

Write-Host "Sending prompt to AI... (this may take a few seconds on your RTX 3050)"
$response = Invoke-RestMethod -Uri "http://localhost:5000/predictions" -Method Post -ContentType "application/json" -Body $body

$base64String = $response.output[0] -replace "^data:image/\w+;base64,", ""
$imageBytes = [Convert]::FromBase64String($base64String)
[IO.File]::WriteAllBytes("$PWD\image_test.png", $imageBytes)

Write-Host "Done! Image saved to: $PWD\image_test.png"