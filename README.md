# AURU — Secure Academic & Identity Document Vault

**CSE472 Cloud Computing Course Project | Team of 2 | Amazon Web Services (LAMP Stack)**

AURU is a cloud-based document verification platform built for a real-world problem: authorities (traffic police, university officials, employers) cannot trust photocopies or phone screenshots as proof of identity. They need instant, trustworthy confirmation that a document — driving license, taxation card, NID, transcript, or university ID — is genuine and issued by a recognized institution.

Instead of showing a static image, users display documents on AURU. Each document receives a unique **verification code**. The verifier enters this code on the public verification portal and immediately sees the document status: **Verified & Authentic**, **Pending**, or **Rejected**. Trust shifts from the paper to the platform.

## Why AURU Exists

| Problem | How AURU Solves It |
|---------|-------------------|
| Fake / edited document images shown to police or admins | Unique verification code linked to platform-backed status |
| No instant way to confirm document authenticity | Public verification portal returns status in seconds |
| Citizens must carry originals everywhere | Digital vault accessible from any browser |
| Verification traffic spikes during festivals, result days, admissions | Auto Scaling Group launches new instances from custom AMI under CPU load |
| Universities issue transcripts/IDs in isolation | Piloted at university level, scalable to government issuers (BRTA, NID, taxation) |

## System Architecture

- **Frontend:** PHP + HTML5 + CSS3 (vanilla JavaScript, no frameworks)
- **Backend:** Apache 2 + PHP 8.x on Ubuntu 22.04 LTS (EC2)
- **Database:** MySQL 8 (local to instance, port 3306 blocked from public internet)
- **Admin Interface:** phpMyAdmin
- **Cloud:** AWS EC2 (`t2.micro`/`t3.micro`), Custom AMI (`auru-v1`), Launch Template, Auto Scaling Group (min 1, desired 2, max 4 across 2 AZs), CloudWatch alarm (CPU > 60% for 2 min → scale-out)
- **Security:** Security Group `auru-sg` (SSH restricted to team IP, HTTP/HTTPS public, MySQL internal-only), CSRF protection, PDO prepared statements, password hashing, `.htaccess` file restrictions, security headers (CSP, X-Frame-Options, Referrer-Policy)

## Repository Structure

```
auru/
├── .htaccess                 # Security headers & direct-access blocking
├── config.php                # PDO connection, session hardening, CSRF, security headers
├── config.example.php        # Credentials template (do not commit real passwords)
├── register.php              # Student signup with password complexity rules
├── login.php                 # Role-based login (student / officer / admin)
├── logout.php                # Session destruction
├── vault.php                 # Personal document dashboard (upload, view, download, delete)
├── upload.php                # File upload with extension + MIME + size validation
├── view.php                  # Inline document viewer (PDF/image renders in browser)
├── download.php              # Owner-only authenticated file download
├── delete.php                # Owner-only document deletion (POST + CSRF)
├── verify.php                # Public officer verification portal (code lookup)
├── admin_verify.php          # Admin approve/reject queue
├── css/
│   └── style.css             # Production-grade UI theme
└── uploads/
    └── .htaccess             # Deny all direct web access to uploaded files

design/                       # Static frontend preview (HTML/CSS, no server required)
preview/                      # Older static mockups
README.md
```

## User Flows

### Student (General User)
1. **Register** → create account (email, password, optional student ID)
2. **Login** → access personal vault
3. **Upload** document (transcript, NID, certificate, driving license, etc.)
4. **View** document inline inside the platform (no forced download)
5. **Copy verification code** and share with verifier
6. **Logout**

### Officer / Verifier (Public Portal)
1. Open `verify.php` (no login required)
2. Enter document verification code (e.g., `AURU-7f2a9b1c`)
3. View status: **Verified & Authentic** / **Pending** / **Rejected**

### Admin (Institutional Verifier)
1. Login as admin
2. View all **pending** documents from all students
3. Click **Verify** or **Reject** → status updates instantly with timestamp and verifier ID

## Elasticity (Festival / Peak Traffic Scenario)

During festivals, result publication days, or admission seasons, verification traffic spikes suddenly. A single server would crash.

AURU demonstrates **rapid elasticity**:
- CloudWatch monitors `CPUUtilization` across the Auto Scaling Group
- If CPU exceeds **60% for 2 minutes**, the ASG automatically launches new instances from the custom AMI `auru-v1`
- Load is distributed across multiple Availability Zones
- When traffic normalizes, excess instances terminate — you pay only for what you use

**Demo proof:** Run `stress --cpu 2 --timeout 300` on an ASG node and watch CloudWatch CPU cross 60% while new instances launch automatically.

## Security Layer

- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS prevention (`htmlspecialchars` output encoding)
- ✅ CSRF protection on all state-changing forms
- ✅ Password hashing (`password_hash` / `password_verify`)
- ✅ Session hardening (`httpOnly`, `SameSite=Lax`, regenerate on login)
- ✅ File upload validation (extension + MIME via `finfo`, 5 MB cap)
- ✅ Uploads directory blocked from direct URL access (`.htaccess`)
- ✅ MySQL port 3306 **not** exposed to public internet (Security Group rule)
- ✅ Security headers: CSP, `X-Frame-Options: DENY`, `Referrer-Policy`

## Deployment

### Prerequisites
- AWS account (Free Tier sufficient)
- EC2 key pair (`cse472-key.pem`)
- Two team members: one software, one cloud deployment

### Quick Deploy
1. Launch Ubuntu EC2 (`t2.micro`/`t3.micro`) with Security Group `auru-sg`
2. Install LAMP: `sudo apt install apache2 mysql-server php libapache2-mod-php phpmyadmin -y`
3. Create `auru_db` and run schema (`users`, `documents`, `verification_logs`)
4. Copy PHP files to `/var/www/html/auru/`
5. Update `config.php` with EC2 database credentials
6. Create custom AMI → Launch Template → Auto Scaling Group
7. Configure CloudWatch alarm for CPU-based scale-out

Visit: `http://<EC2-PUBLIC-IP>/auru/register.php`

## Team Roles

| Member | Role | Responsibility |
|--------|------|----------------|
| Member 1 | Software Development | PHP/MySQL application, UI/UX, security layer |
| Member 2 | Cloud Deployment | AWS EC2, LAMP, AMI, Auto Scaling, stress testing |

## Future Roadmap

- **Phase 1 (Current):** University pilot — transcripts, ID cards, certificates verified by university admin
- **Phase 2:** Extend to government issuers via adapter pattern (BRTA driving license, NID, taxation documents)
- **Phase 3:** Offline QR verification for field officers without internet
- **Phase 4:** Integration with national digital identity frameworks

## License

MIT License — Academic project for CSE472 Cloud Computing.

---

*Built as a proof-of-concept verified document confirmation platform. University pilot first; government-scale verification architecture next.*
