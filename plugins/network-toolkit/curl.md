# Network Toolkit — Complete cURL Reference

All 82+ tools in the Network Toolkit are accessible via HTTP from any terminal using `curl`.
No authentication required. Replace `{HOST}` with your server hostname (e.g. `localhost:5000` in development,
or your deployed domain).

```
BASE_URL = https://{HOST}/plugins/network-toolkit/api
```

---

## Table of Contents

1. [Full Analysis Report](#1-full-analysis-report)
2. [IP Tools](#2-ip-tools)
3. [DNS Tools](#3-dns-tools)
4. [WHOIS / Domain Info](#4-whois--domain-info)
5. [HTTP & Headers](#5-http--headers)
6. [SSL / TLS & Certificates](#6-ssl--tls--certificates)
7. [Email Security](#7-email-security)
8. [Website & Availability](#8-website--availability)
9. [Security Checks](#9-security-checks)
10. [Page Analysis](#10-page-analysis)
11. [Output Flags & Tips](#11-output-flags--tips)
12. [Customisation for Developers](#12-customisation-for-developers)

---

## 1. Full Analysis Report

The `report` action is the flagship command. It runs a full multi-category analysis and streams
a beautiful ANSI-coloured report directly to your terminal. A live progress bar is shown while
data is being fetched; it clears itself when the report begins printing.

### Domain analysis

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com"
```

Sections returned for a domain:
- **Domain Registration** — registrar, registered date, expiry, days left, nameservers, status
- **DNS Records** — A, AAAA, CNAME, MX (with priority), NS, TXT, CAA, SOA
- **Email Security** — SPF, DMARC (with policy), DKIM (8 common selectors tried), MX with IPs
- **SSL / TLS Certificate** — subject CN, issuer, algorithm, serial, validity, chain length, SANs
- **HTTP Response** — status code, response time, server, X-Powered-By, cache-control, redirect chain
- **Security Headers** — HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy,
  Permissions-Policy, COOP, COEP — with a visual score bar and grade
- **IP / Geolocation** — country, region, city, timezone, ISP, ASN for each resolved A record
- **CDN & Technology** — CDN provider, server software, HTTP/3, X-Cache, tech stack detected
- **Page Metadata** — page title, meta description, OG title, OG description, canonical URL

### URL analysis (specific path)

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=https://github.com/login"
```

### IP address analysis

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=8.8.8.8"
```

Sections returned for an IP:
- **Reverse DNS** — PTR hostname
- **Geolocation & Network** — country, region, city, lat/lon, timezone
- **ASN / ISP** — ISP name, org, AS number, AS name
- **Flags** — proxy/VPN detection, datacenter hosting, mobile
- **HTTP Probe** — HTTP status on port 80, server header

### IPv6 address analysis

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=2606:4700:4700::1111"
```

### Report flags

| Flag        | Description                                          | Example                                  |
|-------------|------------------------------------------------------|------------------------------------------|
| `&color=0`  | Strip all ANSI codes — plain text for files / grep   | `...&q=github.com&color=0`              |
| `&fmt=json` | Return structured JSON instead of the text report    | `...&q=github.com&fmt=json`             |

```bash
# Plain text (no colour codes) — safe to redirect to a file
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&color=0" > report.txt

# JSON output — pipe into jq for structured access
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&fmt=json" | jq .ssl

# Extract just the security score
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&fmt=json" | jq .security_headers.score

# Extract all DNS A records
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&fmt=json" | jq .dns.a

# Extract SSL expiry date
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&fmt=json" | jq .ssl.valid_to

# Extract email security status
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&fmt=json" | jq .email

# Extract IP geolocation
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&fmt=json" | jq .ip_geo

# Check WHOIS registrar and expiry
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&fmt=json" | jq '{registrar:.whois.registrar,expires:.whois.expDate}'
```

> **Note on empty values:** Both the text report and JSON output automatically omit empty,
> null, or unavailable fields. You will only see data that actually exists for the target.

---

## 2. IP Tools

### My IP — detect the caller's IP address

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=my_ip"
```

Response: `{"ip":"203.0.113.42"}`

### IP Geolocation — geolocate an IP or domain

```bash
# By IP address
curl "https://{HOST}/plugins/network-toolkit/api?action=ip_geo&q=8.8.8.8"

# By IPv6 address
curl "https://{HOST}/plugins/network-toolkit/api?action=ip_geo&q=2001:4860:4860::8888"

# By domain (resolves to IP first)
curl "https://{HOST}/plugins/network-toolkit/api?action=ip_geo&q=github.com"
```

Response fields: `country`, `countryCode`, `regionName`, `city`, `zip`, `lat`, `lon`,
`timezone`, `isp`, `org`, `as`, `asname`, `mobile`, `proxy`, `hosting`, `query`

```bash
# Pretty print with jq
curl "https://{HOST}/plugins/network-toolkit/api?action=ip_geo&q=1.1.1.1" | jq .

# Extract just the country and ISP
curl "https://{HOST}/plugins/network-toolkit/api?action=ip_geo&q=1.1.1.1" | jq '{country:.country, isp:.isp}'

# Check if an IP is a proxy / VPN
curl "https://{HOST}/plugins/network-toolkit/api?action=ip_geo&q=1.1.1.1" | jq .proxy

# Check if an IP is a datacenter/hosting IP
curl "https://{HOST}/plugins/network-toolkit/api?action=ip_geo&q=8.8.8.8" | jq .hosting
```

### Reverse IP Lookup — hostname, ISP, ASN info for an IP

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=reverse_ip&q=8.8.8.8"

# Domain input is supported too
curl "https://{HOST}/plugins/network-toolkit/api?action=reverse_ip&q=github.com"
```

Response includes: `rdns` (PTR hostname), `ip`, `isp`, `org`, `as`, `asname`, `country`,
`regionName`, `city`, `hosting`, `proxy`, `mobile`

### Reverse DNS (PTR record only)

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=rdns&q=8.8.8.8"

# IPv6
curl "https://{HOST}/plugins/network-toolkit/api?action=rdns&q=2001:4860:4860::8888"
```

Response: `{"ip":"8.8.8.8","host":"dns.google","resolved":true}`

---

## 3. DNS Tools

### DNS Lookup — single record type

```bash
# A records (default)
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com"

# Specify record type with &extra=TYPE
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=A"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=AAAA"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=MX"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=NS"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=TXT"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=CNAME"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=SOA"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=CAA"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=SRV"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=PTR"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=ANY"
```

```bash
# Extract just the IP addresses from A records
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=A" | jq '[.records[].ip]'

# Get MX records sorted by priority
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=gmail.com&extra=MX" | jq '[.records[] | {pri:.pri, host:.target}]'

# Get all TXT records as plain strings
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=TXT" | jq '[.records[].txt]'

# Get NS records
curl "https://{HOST}/plugins/network-toolkit/api?action=dns&q=github.com&extra=NS" | jq '[.records[].target]'
```

### DNS Zone Viewer — all record types at once

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_zone&q=github.com"
```

Returns all of: A, AAAA, MX, TXT, NS, CNAME, SOA, SRV, CAA in one call.

```bash
# Pretty print the full zone
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_zone&q=github.com" | jq .zone

# Extract only record types that exist
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_zone&q=github.com" | jq '.zone | keys'
```

### Domain → IP Resolution

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_ip&q=github.com"

# URL input also works
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_ip&q=https://github.com/login"
```

Response: `{"domain":"github.com","ipv4":["140.82.121.4"],"ipv6":[]}`

```bash
# Get only IPv4 addresses
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_ip&q=github.com" | jq .ipv4

# Get first IP
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_ip&q=github.com" | jq .ipv4[0]
```

### DNS Propagation Checker — query multiple resolvers

```bash
# Default: A records across Google, Cloudflare, Quad9, OpenDNS
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_propagation&q=github.com"

# Other record types
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_propagation&q=github.com&extra=MX"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_propagation&q=github.com&extra=TXT"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_propagation&q=github.com&extra=NS"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_propagation&q=github.com&extra=AAAA"
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_propagation&q=github.com&extra=CNAME"
```

```bash
# Check if all resolvers agree
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_propagation&q=github.com" | \
  jq '[.results[] | {resolver:.resolver, answers:.answers}]'

# Detect propagation discrepancies
curl "https://{HOST}/plugins/network-toolkit/api?action=dns_propagation&q=newdomain.com" | \
  jq '[.results[] | select(.answers | length == 0) | .resolver]'
```

---

## 4. WHOIS / Domain Info

### WHOIS / RDAP Lookup

```bash
# Domain
curl "https://{HOST}/plugins/network-toolkit/api?action=whois&q=github.com"

# IP address (uses ARIN RDAP)
curl "https://{HOST}/plugins/network-toolkit/api?action=whois&q=8.8.8.8"
```

```bash
# Extract registrar and expiry date
curl "https://{HOST}/plugins/network-toolkit/api?action=whois&q=github.com" | \
  jq '{registrar: .entities[0].vcardArray, status: .status, expires: .events}'
```

### Domain Age Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_age&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_age&q=google.com"
```

Response: `{"domain":"github.com","created":"2007-10-09","age_days":6112,"age_years":16.7,...}`

```bash
# Get just the age in years
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_age&q=github.com" | jq .age_years

# Get created and expires dates
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_age&q=github.com" | \
  jq '{created:.created, expires:.expires, age_years:.age_years}'
```

### Domain Expiry Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_expiry&q=github.com"
```

Response: `{"domain":"github.com","expires":"2025-10-09","days_until_expiry":107,"expired":false,...}`

```bash
# Check if domain is expired
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_expiry&q=github.com" | jq .expired

# Days remaining until expiry
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_expiry&q=github.com" | jq .days_until_expiry

# Alert if fewer than 30 days remain
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_expiry&q=github.com" | \
  jq 'if .days_until_expiry < 30 then "⚠ EXPIRING SOON" else "OK" end'
```

### Domain Availability Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_availability&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=domain_availability&q=totallynotregistered12345.com"
```

Response: `{"domain":"...","available":false,"note":"Domain is registered"}`

```bash
# Check availability for multiple TLDs (shell loop)
for tld in com net org io co; do
  result=$(curl -s "https://{HOST}/plugins/network-toolkit/api?action=domain_availability&q=myidea.${tld}")
  available=$(echo $result | jq -r .available)
  echo "myidea.${tld}: ${available}"
done
```

---

## 5. HTTP & Headers

### HTTP Headers — view all response headers

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=http_headers&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=http_headers&q=https://github.com/login"
```

```bash
# Get all header names
curl "https://{HOST}/plugins/network-toolkit/api?action=http_headers&q=github.com" | jq '.headers | keys'

# Get a specific header value
curl "https://{HOST}/plugins/network-toolkit/api?action=http_headers&q=github.com" | jq '.headers["content-type"]'

# Get the raw header string
curl "https://{HOST}/plugins/network-toolkit/api?action=http_headers&q=github.com" | jq -r .raw

# Get status code and response time
curl "https://{HOST}/plugins/network-toolkit/api?action=http_headers&q=github.com" | \
  jq '{code:.code, time_ms:.time_ms}'
```

### Security Headers — check all security response headers

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=security_headers&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=security_headers&q=https://mysite.example"
```

Checks for: `Strict-Transport-Security`, `Content-Security-Policy`, `X-Frame-Options`,
`X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`,
`Cross-Origin-Embedder-Policy`, `X-XSS-Protection`

```bash
# Show only missing security headers
curl "https://{HOST}/plugins/network-toolkit/api?action=security_headers&q=github.com" | \
  jq '[.checks[] | select(.present == false) | .name]'

# Show only present security headers with their values
curl "https://{HOST}/plugins/network-toolkit/api?action=security_headers&q=github.com" | \
  jq '[.checks[] | select(.present == true) | {name:.name, value:.value}]'

# Check for HSTS specifically
curl "https://{HOST}/plugins/network-toolkit/api?action=security_headers&q=github.com" | \
  jq '.checks[] | select(.key == "strict-transport-security")'
```

### Cache Headers — inspect caching configuration

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=cache_headers&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=cache_headers&q=https://cdn.example.com/asset.js"
```

Returns: `cache-control`, `expires`, `etag`, `last-modified`, `vary`, `age`, `pragma`,
`x-cache`, `cf-cache-status`, `surrogate-control`

```bash
# Get cache-control directive
curl "https://{HOST}/plugins/network-toolkit/api?action=cache_headers&q=github.com" | \
  jq '.cache_headers["cache-control"]'

# Check CDN cache status
curl "https://{HOST}/plugins/network-toolkit/api?action=cache_headers&q=github.com" | \
  jq '.cache_headers["cf-cache-status"]'
```

### Redirect Chain — follow and map all redirects

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=redirect_chain&q=http://github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=redirect_chain&q=bit.ly/somelink"
```

```bash
# Show each hop with status code
curl "https://{HOST}/plugins/network-toolkit/api?action=redirect_chain&q=http://github.com" | \
  jq '.chain[] | {url:.url, code:.code}'

# Count total hops
curl "https://{HOST}/plugins/network-toolkit/api?action=redirect_chain&q=http://github.com" | jq .hops

# Get the final URL
curl "https://{HOST}/plugins/network-toolkit/api?action=redirect_chain&q=http://github.com" | jq .final
```

### HTTP Method Tester — check which HTTP methods are allowed

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=http_method&q=https://api.github.com"
```

Tests: `GET`, `HEAD`, `POST`, `PUT`, `DELETE`, `OPTIONS`, `PATCH`

```bash
# Show which methods return 2xx
curl "https://{HOST}/plugins/network-toolkit/api?action=http_method&q=https://httpbin.org/get" | \
  jq '[.results[] | select(.code >= 200 and .code < 300)]'
```

### HTTP Status Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=http_status_check&q=https://github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=http_status_check&q=https://example.com/404"
```

```bash
# Check status code only
curl "https://{HOST}/plugins/network-toolkit/api?action=http_status_check&q=https://github.com" | jq .code

# Check if site is up (2xx)
curl "https://{HOST}/plugins/network-toolkit/api?action=http_status_check&q=https://github.com" | \
  jq 'if .code >= 200 and .code < 300 then "UP" else "DOWN or redirecting" end'
```

### URL Redirect Check — check if a URL redirects

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=url_redirect&q=http://github.com"
```

Response: `{"url":"...","code":301,"location":"https://github.com","redirects":true}`

### Content-Type Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=content_type&q=https://github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=content_type&q=https://example.com/image.png"
```

```bash
# Get MIME type
curl "https://{HOST}/plugins/network-toolkit/api?action=content_type&q=https://github.com" | jq .mime_type

# Get charset
curl "https://{HOST}/plugins/network-toolkit/api?action=content_type&q=https://github.com" | jq .charset
```

### Website Availability — is the site up?

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=availability&q=https://github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=availability&q=https://example.com"
```

```bash
# Is it available? (true/false)
curl "https://{HOST}/plugins/network-toolkit/api?action=availability&q=https://github.com" | jq .available

# Response time in ms
curl "https://{HOST}/plugins/network-toolkit/api?action=availability&q=https://github.com" | jq .time_ms
```

### Response Time — latency benchmark (3 runs)

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=response_time&q=https://github.com"
```

Response: `{"avg_ms":112,"min_ms":98,"max_ms":134,"times_ms":[98,134,105]}`

```bash
# Get average response time
curl "https://{HOST}/plugins/network-toolkit/api?action=response_time&q=https://github.com" | jq .avg_ms

# Full benchmark summary
curl "https://{HOST}/plugins/network-toolkit/api?action=response_time&q=https://github.com" | \
  jq '{avg:.avg_ms, min:.min_ms, max:.max_ms}'
```

### Server Information

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=server_info&q=https://github.com"
```

Returns: `server`, `x-powered-by`, `content-type`, `via`, `cf-ray`, `x-cache`,
`x-request-id`, `alt-svc`, and all headers.

```bash
# Get server software
curl "https://{HOST}/plugins/network-toolkit/api?action=server_info&q=https://github.com" | jq .server

# Check if behind Cloudflare
curl "https://{HOST}/plugins/network-toolkit/api?action=server_info&q=https://github.com" | \
  jq 'if .["cf-ray"] then "Behind Cloudflare" else "Not Cloudflare" end'
```

### Open Redirect Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=open_redirect&q=https://example.com/redirect?url=evil.com"
```

Response: `{"redirects":true,"cross_domain":true,"potentially_vulnerable":true,...}`

```bash
# Is it potentially vulnerable?
curl "https://{HOST}/plugins/network-toolkit/api?action=open_redirect&q=https://example.com/redir" | \
  jq .potentially_vulnerable
```

---

## 6. SSL / TLS & Certificates

### SSL Certificate Inspector

```bash
# Default port 443
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com"

# Custom port
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com&extra=8443"
```

Response includes: `subject`, `issuer`, `valid_from`, `valid_to`, `days_remaining`, `expired`,
`serial`, `version`, `signature_alg`, `san`, `extensions`, `chain`, `chain_length`, `pem`

```bash
# Days until certificate expires
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com" | jq .days_remaining

# Is the cert expired?
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com" | jq .expired

# Issuer organisation
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com" | jq .issuer.O

# Subject Alternative Names (all domains covered)
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com" | jq .san

# Export PEM certificate
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com" | jq -r .pem

# Certificate chain info
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com" | jq .chain

# Alert if cert expires within 14 days
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com" | \
  jq 'if .days_remaining < 14 then "⚠ CERT EXPIRING SOON!" else "OK (\(.days_remaining) days left)" end'
```

### CSR Decoder — decode a Certificate Signing Request

Pass the PEM as URL-encoded `q` parameter:

```bash
CSR="-----BEGIN CERTIFICATE REQUEST-----
MIICvDCCAaQCAQAwdzELMAkGA1UEBhMCVVMxDTALBgNVBAgMBFV0YWgx...
-----END CERTIFICATE REQUEST-----"

curl -G "https://{HOST}/plugins/network-toolkit/api" \
  --data-urlencode "action=csr_decode" \
  --data-urlencode "q=${CSR}"
```

```bash
# Extract subject information
curl -G "https://{HOST}/plugins/network-toolkit/api" \
  --data-urlencode "action=csr_decode" \
  --data-urlencode "q=${CSR}" | jq .subject

# Key size and type
curl -G "https://{HOST}/plugins/network-toolkit/api" \
  --data-urlencode "action=csr_decode" \
  --data-urlencode "q=${CSR}" | jq .key
```

### PEM Certificate Decoder — decode a raw certificate

```bash
CERT="-----BEGIN CERTIFICATE-----
MIIFajCCBQ+gAwIBAgIRAIIQbskP...
-----END CERTIFICATE-----"

curl -G "https://{HOST}/plugins/network-toolkit/api" \
  --data-urlencode "action=pem_decode" \
  --data-urlencode "q=${CERT}"
```

Response includes: `subject`, `issuer`, `valid_from`, `valid_to`, `days_remaining`, `expired`,
`serial`, `version`, `signature_alg`, `san`, `key_usage`, `ext_key_usage`

### CSR Generator — generate a CSR + private key server-side

> ⚠ For convenience/testing only. Never use server-generated private keys in production.

```bash
# Minimal (CN only)
curl "https://{HOST}/plugins/network-toolkit/api?action=csr_generate&q=example.com"

# With full DN fields
curl "https://{HOST}/plugins/network-toolkit/api?action=csr_generate&q=example.com&org=Acme+Corp&city=Austin&state=TX&country=US&extra=2048"

# 4096-bit key
curl "https://{HOST}/plugins/network-toolkit/api?action=csr_generate&q=example.com&extra=4096"
```

CSR Generator parameters:

| Parameter | Description                        | Default |
|-----------|------------------------------------|---------|
| `q`       | Common Name (domain)               | —       |
| `org`     | Organisation name                  | —       |
| `ou`      | Organisational Unit                | —       |
| `city`    | City / Locality                    | —       |
| `state`   | State / Province                   | —       |
| `country` | ISO 2-letter country code          | `US`    |
| `extra`   | Key size (`2048` or `4096`)        | `2048`  |

```bash
# Save CSR and private key to files
curl "https://{HOST}/plugins/network-toolkit/api?action=csr_generate&q=example.com" | \
  jq -r .csr > example.csr

curl "https://{HOST}/plugins/network-toolkit/api?action=csr_generate&q=example.com" | \
  jq -r .private_key > example.key
```

---

## 7. Email Security

### SPF Record Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=spf&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=spf&q=gmail.com"
```

Response: `{"domain":"...","found":true,"spf":"v=spf1 include:...","all_txt":[...]}`

```bash
# Is SPF configured?
curl "https://{HOST}/plugins/network-toolkit/api?action=spf&q=github.com" | jq .found

# Get the full SPF record
curl "https://{HOST}/plugins/network-toolkit/api?action=spf&q=github.com" | jq .spf
```

### DMARC Record Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=dmarc&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=dmarc&q=gmail.com"
```

Response: `{"domain":"...","found":true,"dmarc":"v=DMARC1; p=reject;...",...}`

```bash
# Is DMARC configured?
curl "https://{HOST}/plugins/network-toolkit/api?action=dmarc&q=github.com" | jq .found

# Get the DMARC policy
curl "https://{HOST}/plugins/network-toolkit/api?action=dmarc&q=github.com" | \
  jq '.dmarc | capture("p=(?P<policy>[^;]+)") | .policy'
```

### DKIM Record Checker

```bash
# Default selector
curl "https://{HOST}/plugins/network-toolkit/api?action=dkim&q=github.com"

# Custom selector
curl "https://{HOST}/plugins/network-toolkit/api?action=dkim&q=github.com&extra=selector1"
curl "https://{HOST}/plugins/network-toolkit/api?action=dkim&q=gmail.com&extra=google"
curl "https://{HOST}/plugins/network-toolkit/api?action=dkim&q=mailchimp.com&extra=k1"
```

Common selectors to try: `default`, `google`, `selector1`, `selector2`, `mail`, `k1`, `dkim`, `smtp`

```bash
# Is DKIM found?
curl "https://{HOST}/plugins/network-toolkit/api?action=dkim&q=github.com" | jq .found

# Get the full DKIM record
curl "https://{HOST}/plugins/network-toolkit/api?action=dkim&q=github.com&extra=google" | jq .dkim
```

### MX Record Lookup (with IP resolution)

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=mx&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=mx&q=gmail.com"
```

Response includes: `priority`, `host`, `ip` for each MX record

```bash
# Get all MX hosts sorted by priority
curl "https://{HOST}/plugins/network-toolkit/api?action=mx&q=gmail.com" | \
  jq '[.mx[] | {priority:.priority, host:.host, ip:.ip}]'

# Count MX records
curl "https://{HOST}/plugins/network-toolkit/api?action=mx&q=gmail.com" | jq .count

# Primary MX (lowest priority)
curl "https://{HOST}/plugins/network-toolkit/api?action=mx&q=gmail.com" | jq .mx[0].host
```

---

## 8. Website & Availability

### Robots.txt Viewer

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=robots&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=robots&q=https://example.com"
```

Response: `{"found":true,"url":"...","content":"User-agent: *\nDisallow: /..."}`

```bash
# Is robots.txt present?
curl "https://{HOST}/plugins/network-toolkit/api?action=robots&q=github.com" | jq .found

# Print robots.txt content
curl "https://{HOST}/plugins/network-toolkit/api?action=robots&q=github.com" | jq -r .content

# Check if search engines are blocked
curl "https://{HOST}/plugins/network-toolkit/api?action=robots&q=github.com" | \
  jq '.content | test("Disallow: /$")'
```

### Sitemap Finder & Viewer

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=https://wordpress.org"
```

Response: `{"found":true,"url":"...","url_count":245,"urls":[...],"raw":"..."}`

```bash
# Is sitemap present?
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=github.com" | jq .found

# Count URLs in sitemap
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=github.com" | jq .url_count

# List first 10 URLs
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=github.com" | jq '.urls[:10]'

# Sitemap URL itself
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=github.com" | jq .url
```

### Security.txt Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=security_txt&q=github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=security_txt&q=google.com"
```

Checks both `/.well-known/security.txt` and `/security.txt`.

```bash
# Is security.txt present?
curl "https://{HOST}/plugins/network-toolkit/api?action=security_txt&q=github.com" | jq .found

# Print its content
curl "https://{HOST}/plugins/network-toolkit/api?action=security_txt&q=github.com" | jq -r .content
```

### Canonical URL Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=canonical_url&q=https://github.com/features"
```

Response: `{"url":"...","final_url":"...","canonical":"https://github.com/features","http_code":200}`

```bash
# Get canonical URL
curl "https://{HOST}/plugins/network-toolkit/api?action=canonical_url&q=https://github.com/features" | jq .canonical

# Check if canonical matches final URL
curl "https://{HOST}/plugins/network-toolkit/api?action=canonical_url&q=https://github.com/features" | \
  jq '.canonical == .final_url'
```

---

## 9. Security Checks

### Open Redirect Checker

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=open_redirect&q=https://example.com/redirect?url=http://evil.com"
```

```bash
# Is the redirect cross-domain?
curl "https://{HOST}/plugins/network-toolkit/api?action=open_redirect&q=https://example.com/redir" | \
  jq .cross_domain
```

---

## 10. Page Analysis

### Page Metadata — title, description, OG tags, tech fingerprint

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=page_meta&q=https://github.com"
curl "https://{HOST}/plugins/network-toolkit/api?action=page_meta&q=github.com"
```

Response includes: `title`, `description`, `og_title`, `og_description`, `og_image`, `og_type`,
`og_site_name`, `canonical`, `robots_meta`, `viewport`, `generator`, `charset`, `tech`, `word_count`, `cdn`

```bash
# Get page title
curl "https://{HOST}/plugins/network-toolkit/api?action=page_meta&q=https://github.com" | jq .title

# Get OG image URL
curl "https://{HOST}/plugins/network-toolkit/api?action=page_meta&q=https://github.com" | jq .og_image

# Get detected technologies
curl "https://{HOST}/plugins/network-toolkit/api?action=page_meta&q=https://wordpress.org" | \
  jq '.tech[] | {name:.name, category:.cat}'

# Check if WordPress
curl "https://{HOST}/plugins/network-toolkit/api?action=page_meta&q=https://wordpress.org" | \
  jq '[.tech[].name] | contains(["WordPress"])'

# Get word count
curl "https://{HOST}/plugins/network-toolkit/api?action=page_meta&q=https://github.com" | jq .word_count

# Get CDN provider
curl "https://{HOST}/plugins/network-toolkit/api?action=page_meta&q=https://github.com" | jq .cdn
```

### My Request — echo your own request headers

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=my_request"
```

Response: `{"ip":"...","method":"GET","protocol":"HTTP/1.1","host":"...","headers":{...}}`

```bash
# What IP does the server see?
curl "https://{HOST}/plugins/network-toolkit/api?action=my_request" | jq .ip

# What headers are you sending?
curl "https://{HOST}/plugins/network-toolkit/api?action=my_request" | jq .headers

# Test with a custom user-agent
curl -A "MyBot/1.0" "https://{HOST}/plugins/network-toolkit/api?action=my_request" | jq '.headers["user-agent"]'
```

### Robots.txt Analysis (detailed)

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=robots_txt&q=github.com"
```

Response includes: `found`, `content`, `disallowed_paths`, `allowed_paths`, `crawl_delay`,
`sitemap_urls`

```bash
# Get all disallowed paths
curl "https://{HOST}/plugins/network-toolkit/api?action=robots_txt&q=github.com" | jq .disallowed_paths

# Get sitemap URLs declared in robots.txt
curl "https://{HOST}/plugins/network-toolkit/api?action=robots_txt&q=github.com" | jq .sitemap_urls
```

### Sitemap Analysis (detailed)

```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=github.com"
```

```bash
# Is it a sitemap index?
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=github.com" | jq .is_index

# Child sitemap URLs (for sitemap indexes)
curl "https://{HOST}/plugins/network-toolkit/api?action=sitemap&q=github.com" | jq .child_sitemaps
```

---

## 11. Output Flags & Tips

### Global flags available on every action

| Flag       | Effect                                   |
|------------|------------------------------------------|
| `&q=`      | Main input (domain, IP, URL, text)       |
| `&extra=`  | Secondary input (record type, port, etc.)|
| `&fmt=json`| Force JSON on any action (report action) |
| `&color=0` | Strip ANSI colour codes (report action)  |

### Pipe into jq for filtering

```bash
# Install jq if needed
apt-get install jq   # Debian/Ubuntu
brew install jq      # macOS

# Pretty-print any response
curl "https://{HOST}/plugins/network-toolkit/api?action=ip_geo&q=8.8.8.8" | jq .

# Extract a field
curl "https://{HOST}/plugins/network-toolkit/api?action=ssl_cert&q=github.com" | jq .days_remaining
```

### Pipe report to a file

```bash
# Coloured report to terminal, plain text saved to file
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&color=0" > report.txt

# JSON saved to file
curl "https://{HOST}/plugins/network-toolkit/api?action=report&q=github.com&fmt=json" > report.json
```

### Monitor domain expiry via cron

```bash
# /etc/cron.daily/check-domain-expiry
#!/bin/bash
HOST="your-server.example.com"
DOMAIN="yourdomain.com"
DAYS=$(curl -s "https://${HOST}/plugins/network-toolkit/api?action=domain_expiry&q=${DOMAIN}" | jq .days_until_expiry)
if [ "$DAYS" -lt 30 ]; then
  echo "⚠ ${DOMAIN} expires in ${DAYS} days!" | mail -s "Domain Expiry Alert" admin@example.com
fi
```

### Monitor SSL certificate via cron

```bash
#!/bin/bash
HOST="your-server.example.com"
DOMAIN="yourdomain.com"
DAYS=$(curl -s "https://${HOST}/plugins/network-toolkit/api?action=ssl_cert&q=${DOMAIN}" | jq .days_remaining)
if [ "$DAYS" -lt 14 ]; then
  echo "⚠ SSL cert for ${DOMAIN} expires in ${DAYS} days!" | mail -s "SSL Alert" admin@example.com
fi
```

### Bulk domain check in shell

```bash
#!/bin/bash
HOST="your-server.example.com"
DOMAINS="github.com google.com cloudflare.com"

for domain in $DOMAINS; do
  result=$(curl -s "https://${HOST}/plugins/network-toolkit/api?action=ssl_cert&q=${domain}")
  days=$(echo $result | jq .days_remaining)
  echo "${domain}: ${days} days until SSL expiry"
done
```

---

## 12. Customisation for Developers

The Network Toolkit API is designed to be extended. The backend is in `network-toolkit/api.php`.

### Adding a new action

```php
case 'my_action':
    if (!$q) { echo json_encode(['error' => 'No input']); break; }
    // ... your logic using nt_fetch(), nt_dns(), etc.
    echo json_encode(['result' => $someValue]);
    break;
```

Access it via:
```bash
curl "https://{HOST}/plugins/network-toolkit/api?action=my_action&q=github.com"
```

### Available PHP helper functions

| Function                        | Description                                              |
|---------------------------------|----------------------------------------------------------|
| `nt_fetch($url, $opts)`         | HTTP fetch — returns `body`, `headers`, `code`, `time`  |
| `nt_dns($domain, DNS_TYPE)`     | DNS lookup — returns records array                       |
| `nt_clean_host($q)`             | Strips `https://`, paths — returns bare hostname         |
| `nt_clean_url($q)`              | Ensures URL has `https://` prefix                        |
| `nt_parse_headers($raw)`        | Parses raw HTTP headers into lowercase associative array |
| `nt_detect_cdn($headers)`       | Detects CDN from response headers                        |

### Available ANSI helpers for terminal output

| Function              | Output                                   |
|-----------------------|------------------------------------------|
| `nt_row($label, $val)`| Yellow label + white value line          |
| `nt_sec($title)`      | Cyan section header with rule            |
| `nt_check($ok, $label, $val)` | ✓/✗ check row                |
| `nt_ok($v)`           | Green ✓ prefix                           |
| `nt_bad($v)`          | Red ✗ prefix                             |
| `nt_warn($v)`         | Yellow ⚠ prefix                          |
| `nt_score_bar($score)`| Visual score bar + grade                 |
| `nt_term_header($q, $type)` | Banner header (★ developer tweakable) |
| `nt_term_footer($q)`  | Footer (★ developer tweakable)           |

### ANSI colour constants

```php
AB    // bold
AGY   // dark gray
AWH   // bright white
ACY   // cyan      ABCY  // bright cyan
AGR   // green     ABGR  // bright green
ARE   // red       ABRE  // bright red
AYE   // yellow    ABYE  // bright yellow
AMA   // magenta   ABMA  // bright magenta
ABBL  // bright blue
A0    // reset all
```

### `nt_fetch()` options

```php
nt_fetch($url, [
    'timeout' => 12,        // seconds (default: 12)
    'method'  => 'GET',     // GET, HEAD, POST, PUT, DELETE, OPTIONS, PATCH
    'follow'  => true,      // follow redirects (default: true)
    'headers' => [          // extra request headers
        'Accept: application/json',
        'Authorization: Bearer token',
    ],
]);
```

Returns:
```php
[
    'body'        => '...',   // response body string
    'headers'     => [...],   // parsed lowercase headers (associative array)
    'raw_headers' => '...',   // raw header string
    'code'        => 200,     // HTTP status code (int)
    'time'        => 0.112,   // total request time in seconds (float)
    'final_url'   => '...',   // URL after redirects
    'error'       => null,    // error message string or null
]
```

### Customising the report banner

The header and footer of the `report` action are clearly marked in `api.php`:

```php
/* ★ DEVELOPER TWEAKABLE — HEADER */
function nt_term_header(string $query, string $type): string { ... }

/* ★ DEVELOPER TWEAKABLE — FOOTER */
function nt_term_footer(string $query): string { ... }
```

Edit these functions to change the branding, add fields, or modify the box-drawing style.
The `$type` parameter is one of: `Domain`, `URL`, `IP`, `IPv6`.

---

*Generated for Awan Tools — Network Toolkit · awantools.site*
