#!/bin/bash

# Generate Self-Signed SSL Certificate for IP Address

set -e

IP=${1:-"203.8.25.9"}
DAYS=3650

echo "=== Generating Self-Signed SSL Certificate ==="
echo "IP Address: $IP"
echo "Valid for: $DAYS days"
echo ""

# Create directory structure
mkdir -p docker/ssl/self-signed

# Generate private key and certificate
openssl req -x509 -nodes -days $DAYS -newkey rsa:4096 \
  -keyout docker/ssl/self-signed/key.pem \
  -out docker/ssl/self-signed/cert.pem \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=$IP" \
  -addext "subjectAltName=IP:$IP,DNS:localhost,DNS:*.local" \
  -addext "extendedKeyUsage=serverAuth"

echo ""
echo "✓ Certificate generated successfully!"
echo ""
echo "Files created:"
echo "  - docker/ssl/self-signed/cert.pem"
echo "  - docker/ssl/self-signed/key.pem"
echo ""
echo "⚠️  WARNING: This is a self-signed certificate."
echo "   Browsers will show a security warning."
echo "   You can proceed anyway for testing/internal use."
