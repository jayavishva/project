    </main>
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Mehedi Shop</h5>
                    <p>Your trusted source for premium henna products and accessories.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Contact Us</h5>
                    <p>Email: jayavishva3@gmail.com<br>Phone: +91 7639170568</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-2">
                        <a href="index.php" class="text-light text-decoration-none me-3">Home</a>
                        <?php if (!isLoggedIn()): ?>
                            <a href="login.php" class="text-light text-decoration-none me-3">Login</a>
                            <a href="register.php" class="text-light text-decoration-none me-3">Register</a>
                        <?php else: ?>
                            <a href="profile.php" class="text-light text-decoration-none me-3">Profile</a>
                            <a href="orders.php" class="text-light text-decoration-none me-3">Orders</a>
                        <?php endif; ?>
                        <?php if (isLoggedIn() && isAdmin()): ?>
                            <a href="admin/index.php" class="text-light text-decoration-none me-3">Admin Panel</a>
                        <?php endif; ?>
                    </p>
                    <p>&copy; <?php echo date('Y'); ?> Mehedi Shop. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>


