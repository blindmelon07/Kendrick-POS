# User Guide — Kendrick Food Ordering & POS System

**Version:** 1.0  
**Last Updated:** May 2026  
**Platform:** Laravel 12 · Livewire 4 · Tailwind CSS 4

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Getting Started](#2-getting-started)
   - 2.1 [Account Registration (Customers)](#21-account-registration-customers)
   - 2.2 [Staff Login](#22-staff-login)
   - 2.3 [Two-Factor Authentication (2FA)](#23-two-factor-authentication-2fa)
   - 2.4 [User Roles & Permissions](#24-user-roles--permissions)
3. [Customer Module](#3-customer-module)
   - 3.1 [Browsing the Menu](#31-browsing-the-menu)
   - 3.2 [Daily Menu](#32-daily-menu)
   - 3.3 [Shopping Cart](#33-shopping-cart)
   - 3.4 [Checkout & Payment](#34-checkout--payment)
   - 3.5 [Tracking Your Orders](#35-tracking-your-orders)
4. [POS Terminal (Cashier)](#4-pos-terminal-cashier)
   - 4.1 [Starting a Transaction](#41-starting-a-transaction)
   - 4.2 [Adding Items](#42-adding-items)
   - 4.3 [Applying Discounts & Tax](#43-applying-discounts--tax)
   - 4.4 [Processing Payment](#44-processing-payment)
   - 4.5 [Voiding a Transaction](#45-voiding-a-transaction)
   - 4.6 [Transaction History](#46-transaction-history)
5. [Inventory Management](#5-inventory-management)
   - 5.1 [Product Catalog](#51-product-catalog)
   - 5.2 [Stock In (Receiving Stock)](#52-stock-in-receiving-stock)
   - 5.3 [Stock Adjustments & Log](#53-stock-adjustments--log)
   - 5.4 [Low-Stock Alerts](#54-low-stock-alerts)
6. [Supplier Management](#6-supplier-management)
   - 6.1 [Supplier Directory](#61-supplier-directory)
   - 6.2 [Purchase Orders](#62-purchase-orders)
   - 6.3 [Delivery Orders](#63-delivery-orders)
7. [Order Management (Admin/Manager)](#7-order-management-adminmanager)
   - 7.1 [Viewing All Orders](#71-viewing-all-orders)
   - 7.2 [Creating Manual Orders](#72-creating-manual-orders)
   - 7.3 [Customer Management](#73-customer-management)
8. [Daily Menu Management](#8-daily-menu-management)
9. [Employee & HR Management](#9-employee--hr-management)
   - 9.1 [Employee Directory](#91-employee-directory)
   - 9.2 [Attendance Tracking](#92-attendance-tracking)
   - 9.3 [Payroll Deductions](#93-payroll-deductions)
   - 9.4 [Cash Advances](#94-cash-advances)
10. [User Management (Admin)](#10-user-management-admin)
11. [Settings](#11-settings)
    - 11.1 [Profile Settings](#111-profile-settings)
    - 11.2 [Appearance](#112-appearance)
    - 11.3 [Security](#113-security)
    - 11.4 [Site Settings (Admin)](#114-site-settings-admin)
12. [System Backup (Admin)](#12-system-backup-admin)
13. [Payment Processing Reference](#13-payment-processing-reference)
14. [Troubleshooting](#14-troubleshooting)
15. [Role-Based Access Quick Reference](#15-role-based-access-quick-reference)

---

## 1. System Overview

The **Kendrick** system is an all-in-one food ordering and retail management platform that combines:

| Module | Description |
|--------|-------------|
| **Online Ordering** | Customer-facing menu, cart, and checkout |
| **POS Terminal** | In-store point-of-sale for cashiers |
| **Inventory** | Product catalog, stock levels, and movement logs |
| **Suppliers** | Purchase orders and delivery tracking |
| **HR** | Employee profiles, attendance, payroll deductions |
| **Settings** | Site configuration, user profiles, and security |

The system supports three staff roles — **Admin**, **Manager**, and **Cashier** — plus a separate **Customer** account type for online shoppers.

---

## 2. Getting Started

### 2.1 Account Registration (Customers)

Customers who want to place online orders must create an account.

1. Go to **`/customer/register`** from the navigation bar.
2. Fill in:
   - Full name
   - Email address
   - Password (and confirmation)
3. Click **Register**.
4. You will receive a verification email — click the link inside to activate your account.
5. Once verified, log in at **`/customer/login`**.

> **Note:** Customer accounts are separate from staff accounts. Customers can only access the public ordering pages, cart, checkout, and order history.

---

### 2.2 Staff Login

Staff members (Admin, Manager, Cashier) use the standard login page:

1. Navigate to **`/login`**.
2. Enter your work **email** and **password**.
3. If Two-Factor Authentication is enabled on your account, you will be prompted for a verification code (see §2.3).
4. After successful login you are redirected to the **Dashboard**.

> If you forget your password, use the **Forgot Password** link on the login page.

---

### 2.3 Two-Factor Authentication (2FA)

2FA adds an extra layer of security using a time-based one-time password (TOTP) app such as Google Authenticator or Authy.

**Enabling 2FA:**

1. Go to **Settings → Security**.
2. Click **Enable Two-Factor Authentication**.
3. Scan the QR code with your authenticator app.
4. Enter the 6-digit code shown in the app to confirm.
5. Save your **recovery codes** in a secure place — these can be used if you lose access to your authenticator app.

**Logging in with 2FA active:**

1. Enter your email and password as usual.
2. On the next screen, enter the 6-digit code from your authenticator app.
3. Alternatively, enter one of your saved recovery codes.

**Disabling 2FA:**

1. Go to **Settings → Security**.
2. Click **Disable Two-Factor Authentication** and confirm your password.

---

### 2.4 User Roles & Permissions

| Role | What They Can Access |
|------|----------------------|
| **Admin** | Everything — all modules, site settings, user management, backup |
| **Manager** | Inventory, suppliers, orders, daily menu, employees, customers, HR |
| **Cashier** | POS terminal, transaction history |
| **Customer** | Public menu, cart, checkout, own order history |

Role assignments are managed by an Admin in the **User Management** section (see §10).

---

## 3. Customer Module

### 3.1 Browsing the Menu

The public menu is accessible at **`/menu`** without logging in.

- Products are organized by **category**.
- Use the **category filter** tabs at the top to narrow the list.
- Each product card shows:
  - Product image
  - Product name
  - Price
  - **Add to Cart** button
- Use the **search bar** to find specific items by name.

---

### 3.2 Daily Menu

The **Home page (`/`)** highlights today's featured items set by the manager. These are curated picks that may change daily. Click any item to add it directly to your cart.

---

### 3.3 Shopping Cart

Accessible at **`/cart`**.

| Action | How To |
|--------|--------|
| View cart | Click the cart icon or navigate to `/cart` |
| Increase quantity | Click **+** next to an item |
| Decrease quantity | Click **-** next to an item |
| Remove item | Click the **trash** icon |
| View subtotal | Shown at the bottom of the item list |

The cart persists in your browser session. Logging out will clear the cart.

---

### 3.4 Checkout & Payment

1. From the cart, click **Proceed to Checkout**.
2. You must be logged in as a customer — you will be prompted to log in if not.
3. On the **Checkout** page (`/checkout`):
   - Confirm your delivery details (name, address, contact number).
   - Review your order summary and total.
   - Select a **payment method:**

| Payment Method | Description |
|----------------|-------------|
| **GCash** | Mobile wallet via PayMongo |
| **PayMaya** | Mobile wallet via PayMongo |
| **Credit/Debit Card** | Visa/Mastercard via PayMongo |
| **Cash on Delivery** | Pay when your order arrives |

4. Click **Place Order**.
   - For online payments, you will be redirected to the PayMongo secure checkout page.
   - Complete payment there and you will be sent back to a confirmation page.
   - For Cash on Delivery, your order is placed immediately.

5. After successful payment, you will see a **Payment Successful** confirmation.

---

### 3.5 Tracking Your Orders

Navigate to **`/my-orders`** after logging in.

- Each order shows:
  - Order number and date
  - Items ordered with quantities and price
  - Payment status (Pending, Paid, Failed)
  - Order status
- Click on any order to view its full details.

---

## 4. POS Terminal (Cashier)

The POS Terminal is used by **Cashiers** and **Admins** for in-store transactions.

Navigate to **`/pos`**.

---

### 4.1 Starting a Transaction

The POS screen loads with an empty cart on the right side and the product list on the left.

- All available products with stock are displayed.
- Use the **search bar** to quickly find items by name or SKU.
- Use the **barcode scanner** field to scan a product's barcode directly.

---

### 4.2 Adding Items

**Method 1 — Click:**  
Click the product card to add one unit to the cart.

**Method 2 — Barcode:**  
Focus the barcode input field, then scan the product barcode with a scanner. The item is added automatically.

**Method 3 — QR Code:**  
Use the QR scanner button to activate the camera-based QR reader.

**Adjusting quantity in the cart:**

- Click **+** or **-** buttons next to the item in the cart.
- Or click the quantity number and type the desired amount directly.
- Click the **trash** icon to remove an item.

---

### 4.3 Applying Discounts & Tax

In the cart summary area:

- **Discount:** Enter a discount amount (fixed value) or percentage in the discount field.
- **Tax:** Tax is calculated automatically based on system configuration. The pre-tax and tax amounts are shown separately.
- The **Total** at the bottom reflects all adjustments.

---

### 4.4 Processing Payment

1. Confirm the cart items and total are correct.
2. Click **Charge / Process Payment**.
3. Enter the **amount tendered** by the customer.
4. The system calculates and displays the **change**.
5. Click **Confirm** to complete the transaction.
6. A receipt summary is shown. Print or dismiss it to start a new transaction.

---

### 4.5 Voiding a Transaction

If a transaction was completed in error:

1. Go to **`/pos/history`** (Transaction History).
2. Find the transaction you need to void.
3. Click **Void** on that transaction row.
4. Confirm the void action.

> Voided transactions are marked in the history and the inventory stock is restored. The audit trail is preserved.

---

### 4.6 Transaction History

Navigate to **`/pos/history`**.

- View all completed and voided transactions.
- Filter by **date range** or **transaction status**.
- Click any transaction to see its full line-item breakdown.
- Total sales for the selected period are summarized at the top.

---

## 5. Inventory Management

Accessible by **Manager** and **Admin** roles.

---

### 5.1 Product Catalog

Navigate to **`/inventory/products`**.

**Viewing Products:**
- Products are listed in a table with: SKU, name, category, unit, cost price, selling price, current stock, and reorder level.
- Use the **search bar** to filter by name or SKU.
- Filter by **category** using the dropdown.

**Adding a New Product:**

1. Click **Add Product**.
2. Fill in the product form:
   | Field | Description |
   |-------|-------------|
   | Name | Product display name |
   | SKU | Unique stock-keeping unit code |
   | Barcode | Optional barcode number |
   | Category | Select from existing categories |
   | Unit | Unit of measurement (e.g., pcs, kg, box) |
   | Cost Price | Purchase cost per unit |
   | Selling Price | Retail price per unit |
   | Current Stock | Opening stock quantity |
   | Reorder Level | Minimum stock before low-stock alert |
   | Description | Optional product description |
   | Image | Upload a product photo |
3. Click **Save**.

**Editing a Product:**

1. Click the **Edit** (pencil) icon on the product row.
2. Modify the desired fields.
3. Click **Update**.

**Deactivating/Archiving a Product:**

Products can be set as inactive to hide them from the POS and menu without deleting them. Toggle the **Active** status on the edit form.

---

### 5.2 Stock In (Receiving Stock)

Navigate to **`/inventory/stock`**.

Use this page to record incoming stock (from a supplier delivery or manual addition).

1. Click **Stock In**.
2. Select the **Product**.
3. Enter the **quantity** received.
4. Select the **movement type** (e.g., Purchase, Initial Stock).
5. Optionally link to a **Purchase Order** or **Delivery Order**.
6. Add any notes.
7. Click **Save**.

The stock level for the product is updated immediately, and a movement record is created in the log.

---

### 5.3 Stock Adjustments & Log

Navigate to **`/inventory/log`**.

The **Adjustment Log** shows every stock movement in the system:

| Movement Type | Trigger |
|---------------|---------|
| Purchase | Stock received from supplier |
| Sale | Items sold via POS |
| Adjustment | Manual stock correction |
| Void | Stock restored from a voided POS transaction |

**Making a Stock Adjustment:**

Use this when correcting a discrepancy found during physical inventory count.

1. Click **Adjust Stock**.
2. Select the product.
3. Enter the **new quantity** or the **difference** (positive or negative).
4. Enter a **reason** for the adjustment.
5. Click **Save**.

---

### 5.4 Low-Stock Alerts

Products whose current stock falls at or below the **Reorder Level** are flagged automatically. You can see these highlighted on the product list with a warning indicator.

---

## 6. Supplier Management

Accessible by **Manager** and **Admin** roles.

---

### 6.1 Supplier Directory

Navigate to **`/suppliers`**.

**Adding a Supplier:**

1. Click **Add Supplier**.
2. Enter:
   - Supplier name
   - Contact person
   - Phone number
   - Email address
   - Address
3. Click **Save**.

**Editing a Supplier:**  
Click the **Edit** icon on the supplier row and update the details.

---

### 6.2 Purchase Orders

A Purchase Order (PO) is a formal request sent to a supplier for goods.

Navigate to **`/suppliers`** and click **Purchase Orders**, or look for the PO section within the supplier record.

**Creating a Purchase Order:**

1. Click **New Purchase Order**.
2. Select the **Supplier**.
3. Add line items:
   - Product
   - Quantity ordered
   - Unit cost
4. The PO number is generated automatically.
5. Set the **expected delivery date**.
6. Click **Save** to create the PO in **Draft** status.
7. Click **Submit** to send/mark it as submitted to the supplier.

**PO Statuses:**

| Status | Meaning |
|--------|---------|
| Draft | Being prepared, not yet sent |
| Submitted | Sent to supplier |
| Partially Received | Some items have arrived |
| Received | All items received |
| Cancelled | PO was cancelled |

---

### 6.3 Delivery Orders

Navigate to **`/deliveries`**.

Delivery orders track actual shipments received from suppliers.

**Creating a Delivery Order:**

1. Click **New Delivery** or go to **`/deliveries/create`**.
2. Select the related **Purchase Order** (optional) and **Supplier**.
3. Enter:
   - Expected delivery date
   - Actual received date (when applicable)
4. Add items received with quantities.
5. Click **Save**.

**Receiving a Delivery:**

When goods physically arrive:

1. Open the delivery order.
2. Enter the **received quantities** for each item.
3. Set the status to **Received**.
4. Stock levels are updated automatically.

---

## 7. Order Management (Admin/Manager)

Navigate to **`/orders`**.

---

### 7.1 Viewing All Orders

The Orders page shows all online orders placed through the customer portal.

- Filter by **status** (Pending, Paid, Cancelled, etc.).
- Filter by **date range**.
- Click any order row to view full details including:
  - Customer information
  - Items ordered
  - Payment method and status
  - Delivery details

**Updating an Order Status:**

1. Open the order.
2. Change the status from the dropdown (e.g., mark as Preparing, Out for Delivery, Delivered).
3. Click **Update**.

---

### 7.2 Creating Manual Orders

Admins and Managers can create orders on behalf of customers (e.g., phone orders).

1. Go to **`/orders/create`**.
2. Select or search for a customer.
3. Add items and quantities.
4. Enter delivery details.
5. Select payment method.
6. Click **Place Order**.

---

### 7.3 Customer Management

Navigate to **`/customers`**.

- View all registered customers with contact info and order history.
- Search by name or email.
- Set a customer as **Active** or **Inactive**.
- View a customer's order history by clicking their row.

**Adding a Customer manually:**

1. Click **Add Customer**.
2. Enter name, email, and contact number.
3. Click **Save**.

---

## 8. Daily Menu Management

Accessible by **Manager** and **Admin** roles. Navigate to **`/daily-menu`**.

The Daily Menu lets you curate a list of featured items shown on the home page each day.

**Setting Today's Featured Items:**

1. Click **Add to Daily Menu** or **+ Add Item**.
2. Search for and select a product (only active products with stock appear).
3. Set the **sort order** to control display sequence.
4. Click **Save**.

**Reordering Items:**

Drag and drop items in the list to change their display order, or edit the **Sort Order** number field.

**Removing an Item:**

Click the **Remove** (trash) icon next to an item to take it off the daily menu. The product itself is not affected.

---

## 9. Employee & HR Management

Accessible by **Manager** and **Admin** roles.

---

### 9.1 Employee Directory

Navigate to **`/employees`**.

**Adding an Employee:**

1. Click **Add Employee**.
2. Fill in:
   | Field | Description |
   |-------|-------------|
   | Full Name | Employee's legal name |
   | Employee Number | Auto-generated; can be overridden |
   | Position/Job Title | Role in the organization |
   | Employment Type | Regular, Contractual, Part-time, etc. |
   | Salary | Monthly or daily rate |
   | Start Date | Date of employment |
   | Contact Number | Mobile/phone |
   | Address | Home address |
3. Click **Save**.

**Viewing an Employee Profile:**

Click any employee row or navigate to **`/employees/{employeeId}`** to see:
- Personal and employment details
- Attendance records
- Deductions
- Cash advances

---

### 9.2 Attendance Tracking

From an employee's profile page:

1. Click **Log Attendance** or **Add Attendance Record**.
2. Enter the **date**, **time in**, and **time out**.
3. Select the **attendance type** (Present, Late, Absent, Leave, etc.).
4. Click **Save**.

Attendance records are listed chronologically on the employee profile and can be filtered by month.

---

### 9.3 Payroll Deductions

Deductions reduce an employee's net pay and are recorded on the employee profile.

**Adding a Deduction:**

1. Open the employee profile.
2. Go to the **Deductions** tab.
3. Click **Add Deduction**.
4. Enter:
   - Deduction type (SSS, PhilHealth, Pag-IBIG, Other)
   - Amount
   - Date
   - Notes (optional)
5. Click **Save**.

---

### 9.4 Cash Advances

Cash advances record salary advances given to employees before payday.

**Recording a Cash Advance:**

1. Open the employee profile.
2. Go to the **Cash Advances** tab.
3. Click **Add Cash Advance**.
4. Enter the amount, date, and any notes.
5. Click **Save**.

Cash advances are tracked separately and can be marked as **repaid** when deducted from future payroll.

---

## 10. User Management (Admin)

Navigate to **`/users`** — available to **Admin** only.

This section manages all staff accounts (not customer accounts).

**Viewing Users:**

All staff users are listed with their name, email, role, and account status.

**Creating a Staff Account:**

1. Click **Add User**.
2. Enter:
   - Full name
   - Email address
   - Temporary password
   - Assign a **role** (Admin, Manager, Cashier)
3. Click **Save**.

The new user will receive an invitation/welcome email and can change their password from Settings.

**Changing a User's Role:**

1. Click **Edit** on a user row.
2. Change the **role** dropdown.
3. Click **Update**.

**Deactivating a User:**

1. Click **Edit** on a user row.
2. Toggle **Active** to off.
3. Click **Update**.

Deactivated users cannot log in but their records are preserved.

---

## 11. Settings

All logged-in users can access their personal settings via the **Settings** menu.

---

### 11.1 Profile Settings

Navigate to **`/settings/profile`**.

- Update your **display name**.
- Update your **email address** (requires email re-verification).
- Upload a **profile photo** (if supported).
- Click **Save** to apply changes.

---

### 11.2 Appearance

Navigate to **`/settings/appearance`**.

- Toggle between **Light** and **Dark** mode.
- Select your preferred **color theme** (if multiple themes are configured).
- Changes apply immediately and are saved to your account.

---

### 11.3 Security

Navigate to **`/settings/security`**.

**Changing Your Password:**

1. Enter your **current password**.
2. Enter and confirm your **new password**.
3. Click **Update Password**.

**Two-Factor Authentication:**

See [§2.3](#23-two-factor-authentication-2fa) for full setup instructions.

**Recovery Codes:**

- Recovery codes are shown once during 2FA setup.
- To regenerate them, click **Regenerate Recovery Codes** in the Security settings.
- Store these codes securely offline.

---

### 11.4 Site Settings (Admin)

Navigate to **`/settings/site`** — available to **Admin** only.

Configure system-wide display settings:

| Setting | Description |
|---------|-------------|
| **Site Name** | Business name shown in the header |
| **Logo** | Upload your business logo |
| **Background Image** | Background for public pages |
| **Other Branding** | Colors, tagline, etc. |

Click **Save** after making changes. Settings are cached for performance; changes take effect shortly.

---

## 12. System Backup (Admin)

Navigate to **`/admin/backup`** — available to **Admin** only.

The backup feature (powered by Spatie Laravel Backup) allows you to create and manage database backups.

**Creating a Backup:**

1. On the backup page, click **Run Backup Now**.
2. Wait for the process to complete (this may take a minute).
3. The new backup appears in the list with timestamp and file size.

**Downloading a Backup:**

Click the **Download** button next to a backup file to save it to your computer.

**Deleting Old Backups:**

Click **Delete** on any backup you no longer need to free up storage space.

> **Best Practice:** Schedule regular backups before system updates or major data imports. Store downloaded backups in a secure, off-site location.

---

## 13. Payment Processing Reference

The system integrates with **PayMongo** for online payments.

### Supported Payment Methods

| Method | Type | Notes |
|--------|------|-------|
| GCash | E-wallet | Philippines mobile wallet |
| PayMaya / Maya | E-wallet | Philippines mobile wallet |
| Credit/Debit Card | Card | Visa and Mastercard |
| Cash on Delivery | Offline | No PayMongo processing |

### Payment Flow

1. Customer places order and selects an online payment method.
2. System creates a **PayMongo Checkout Session**.
3. Customer is redirected to the PayMongo-hosted payment page.
4. After payment, PayMongo sends a **webhook** to the system to update order status.
5. Customer is redirected back to a confirmation page.

### Payment Statuses

| Status | Meaning |
|--------|---------|
| Pending | Order placed, payment not yet completed |
| Paid | Payment confirmed by PayMongo |
| Failed | Payment attempt failed |
| Cancelled | Customer cancelled during checkout |

### Troubleshooting Payments

- If a customer's payment status remains **Pending** after they claim to have paid, check the PayMongo dashboard directly for the transaction.
- Webhook delivery can be delayed. Wait a few minutes, then refresh the order status.
- If status still does not update, an Admin can manually update the order status from **`/orders`**.

---

## 14. Troubleshooting

### Cannot Log In

- Ensure Caps Lock is off.
- Use the **Forgot Password** link to reset your password.
- If your account was deactivated, contact your system Admin.

### 2FA Code Not Working

- Make sure your device's clock is accurate (TOTP is time-based).
- Use a **recovery code** if available.
- Contact your Admin to disable 2FA if you are locked out.

### Items Not Showing in Menu or POS

- The product may be set as **Inactive**. Check in Inventory → Products.
- The product may be **out of stock** (stock = 0). Add stock in Inventory → Stock In.

### Payment Redirect Not Working

- Ensure your browser does not block pop-ups or redirects from the site.
- Check that PayMongo credentials are correctly configured in the system environment settings.

### Low Stock Items Not Highlighted

- Verify that the product's **Reorder Level** is set to a value greater than 0 in the product settings.

### Barcode Scanner Not Working at POS

- Ensure the barcode input field is focused (click on it) before scanning.
- Check that the product has a barcode entered in its product record.
- Test the scanner on a text editor to confirm the scanner device is working.

### Page Loads Slowly or Shows Errors

- Clear your browser cache and cookies.
- Try a hard refresh (Ctrl+Shift+R or Cmd+Shift+R).
- Contact your system administrator if the issue persists.

---

## 15. Role-Based Access Quick Reference

| Feature | Admin | Manager | Cashier | Customer |
|---------|:-----:|:-------:|:-------:|:--------:|
| View public menu | ✓ | ✓ | ✓ | ✓ |
| Place online orders | ✓ | ✓ | ✓ | ✓ |
| POS Terminal | ✓ | — | ✓ | — |
| Transaction History | ✓ | — | ✓ | — |
| Inventory / Products | ✓ | ✓ | — | — |
| Stock In / Adjustments | ✓ | ✓ | — | — |
| Suppliers & POs | ✓ | ✓ | — | — |
| Deliveries | ✓ | ✓ | — | — |
| Order Management | ✓ | ✓ | — | — |
| Customer Management | ✓ | ✓ | — | — |
| Daily Menu | ✓ | ✓ | — | — |
| Employee HR | ✓ | ✓ | — | — |
| User Management | ✓ | — | — | — |
| Site Settings | ✓ | — | — | — |
| Database Backup | ✓ | — | — | — |
| Personal Settings | ✓ | ✓ | ✓ | ✓ |

---

*For technical support or to report a bug, contact your system administrator.*
