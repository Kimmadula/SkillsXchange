# 🌐 Domain Verification Steps for skillsxchange.com

## 🎯 **Current Status:**
- ✅ Domain added to Resend: `skillsxchange.com`
- ⏳ DNS records need to be added for verification
- 🔄 Waiting for domain verification

## 📋 **DNS Records to Add:**

Based on the Resend interface, you need to add these DNS records to your domain:

### **1. MX Record (Required)**
```
Type: MX
Host/Name: send
Value: feedback-smtp.ap-northeast-1.amazonses.com
Priority: 10
TTL: Auto
```

### **2. TXT Record - SPF (Required)**
```
Type: TXT
Host/Name: send
Value: v=spf1 include:amazonses.com ~all
TTL: Auto
```

### **3. TXT Record - DKIM (Required)**
```
Type: TXT
Host/Name: resend._domainkey
Value: p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC... (long key)
TTL: Auto
```

### **4. TXT Record - DMARC (Recommended)**
```
Type: TXT
Host/Name: _dmarc
Value: v=DMARC1; p=none;
TTL: Auto
```

## 🔧 **How to Add DNS Records:**

### **Step 1: Access Your Domain Provider**
- Go to your domain registrar (GoDaddy, Namecheap, Cloudflare, etc.)
- Log in to your account
- Find DNS management or DNS settings

### **Step 2: Add Each Record**
1. **Click "Add Record" or "Add DNS Record"**
2. **Select the Type** (MX, TXT)
3. **Enter the Host/Name** (send, resend._domainkey, _dmarc)
4. **Enter the Value** (copy exactly from Resend)
5. **Set Priority** (10 for MX record)
6. **Set TTL** (Auto or 3600)
7. **Save the record**

### **Step 3: Wait for Propagation**
- **DNS changes** can take 5-60 minutes to propagate
- **Check status** in Resend dashboard
- **Refresh the page** to see verification status

### **Step 4: Verify in Resend**
1. **Go back to Resend:** https://resend.com/domains
2. **Click "I've added the records"** button
3. **Wait for verification** (usually 5-10 minutes)
4. **Status should change** to "Verified" ✅

## 🚀 **After Verification:**

### **Update Render Environment Variables:**
1. **Go to Render Dashboard:** https://dashboard.render.com/
2. **Find your SkillsXchange service**
3. **Click "Environment" tab**
4. **Update these variables:**
   ```
   MAIL_FROM_ADDRESS=noreply@skillsxchange.com
   MAIL_FROM_NAME=SkillsXchange
   RESEND_API_KEY=re_KZXcNx4W_7fdSyXJjjHYkokLUsN5czjWt
   ```

### **Deploy Changes:**
1. **Click "Manual Deploy"** in Render
2. **Select "Deploy latest commit"**
3. **Wait for deployment**

### **Test Email Functionality:**
1. **Go to your app:** https://skillsxchange-crus.onrender.com
2. **Try registering a new user**
3. **Check if verification email is sent**
4. **Verify the email comes from:** `noreply@skillsxchange.com`

## ✅ **Expected Result:**
- ✅ **Domain verified** in Resend dashboard
- ✅ **Emails sent from** `noreply@skillsxchange.com`
- ✅ **Professional email address** for your application
- ✅ **High deliverability** rates

## 📞 **If Verification Fails:**
1. **Double-check** DNS records are added correctly
2. **Wait longer** for DNS propagation (up to 24 hours)
3. **Check** if your domain provider has any restrictions
4. **Contact** Resend support if issues persist

**Once verified, your email system will be fully functional!** 🎉
