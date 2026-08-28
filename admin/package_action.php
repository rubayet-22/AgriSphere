<?php
/**
 * Admin - Package Actions (create / delete / activate / deactivate)
 *
 * Plain CRUD over the two tables the Facade reads: product_package and
 * package_item. It writes with mysqli the same way the other admin
 * maintenance pages do.
 *
 * This deliberately does NOT touch PackageFacade: the Facade's job is adding a
 * package to a customer's cart, which is a different concern from an
 * administrator maintaining the package catalogue. Both simply use the same
 * two tables.
 */

require_once __DIR__ . '/../includes/header.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('error', 'Unauthorized access');
    redirect(BASE_URL . 'auth/login.php?role=admin');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'admin/packages.php');
}

$action = sanitize($_POST['action'] ?? '');

// Create a package from the products the admin ticked
if ($action === 'create') {
    $name        = sanitize($_POST['package_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $productIds  = $_POST['product_id'] ?? [];
    $quantities  = $_POST['quantity'] ?? [];

    if ($name === '') {
        setFlashMessage('error', 'Please give the package a name.');
        redirect(BASE_URL . 'admin/packages.php');
    }

    // Keep only ticked products that were given a quantity of at least 1.
    $items = [];
    foreach ($productIds as $productId) {
        $productId = (int)$productId;
        $quantity  = (int)($quantities[$productId] ?? 0);
        if ($productId > 0 && $quantity > 0) {
            $items[$productId] = $quantity;
        }
    }

    if (count($items) < 2) {
        setFlashMessage('error', 'A package needs at least 2 products, each with a quantity of 1 or more.');
        redirect(BASE_URL . 'admin/packages.php');
    }

    // One transaction: a package must never end up half-built.
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO product_package (package_name, description, is_active) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $name, $description);
        $stmt->execute();
        $packageId = $conn->insert_id;

        $stmt = $conn->prepare("INSERT INTO package_item (package_id, product_id, quantity) VALUES (?, ?, ?)");
        foreach ($items as $productId => $quantity) {
            $stmt->bind_param("iii", $packageId, $productId, $quantity);
            $stmt->execute();
        }

        $conn->commit();
        setFlashMessage('success', 'Package "' . $name . '" created with ' . count($items) . ' products.');

    } catch (Throwable $e) {
        $conn->rollback();
        // 1062 is the UNIQUE key on package_name.
        $message = ($conn->errno === 1062)
            ? 'A package called "' . $name . '" already exists.'
            : 'Could not create the package: ' . $e->getMessage();
        setFlashMessage('error', $message);
    }

    redirect(BASE_URL . 'admin/packages.php');
}

// Delete a package (package_item rows go with it via ON DELETE CASCADE)
if ($action === 'delete') {
    $packageId = (int)($_POST['package_id'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM product_package WHERE package_id = ?");
    $stmt->bind_param("i", $packageId);
    $stmt->execute();

    setFlashMessage(
        $stmt->affected_rows > 0 ? 'success' : 'error',
        $stmt->affected_rows > 0 ? 'Package deleted.' : 'Package not found.'
    );
    redirect(BASE_URL . 'admin/packages.php');
}

// Show / hide a package on the customer Packages page
if ($action === 'toggle') {
    $packageId = (int)($_POST['package_id'] ?? 0);

    $stmt = $conn->prepare("UPDATE product_package SET is_active = 1 - is_active WHERE package_id = ?");
    $stmt->bind_param("i", $packageId);
    $stmt->execute();

    setFlashMessage('success', 'Package visibility updated.');
    redirect(BASE_URL . 'admin/packages.php');
}

setFlashMessage('error', 'Invalid action');
redirect(BASE_URL . 'admin/packages.php');
