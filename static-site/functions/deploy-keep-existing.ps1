# Deploy Cloud Functions while keeping existing functions
Write-Host "Deploying Cloud Functions (keeping existing functions)..." -ForegroundColor Cyan

# Use echo to automatically answer 'N' to keep existing functions
echo "N" | firebase deploy --only functions

Write-Host ""
Write-Host "Deployment complete!" -ForegroundColor Green

