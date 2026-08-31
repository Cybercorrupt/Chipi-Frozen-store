<?php
require_once __DIR__ . '/../includes/functions.php';
unset($_SESSION['customer_id']);
flash('success', 'Anda telah keluar.');
redirect('index.php');
