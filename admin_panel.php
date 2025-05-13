<?php
// Start session
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.html");
    exit();
}

// Include database configuration
require_once 'config.php';

// Get all users
$sql = "SELECT id, fullname, email, username, created_at FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | Bakhyt Zharkynbek</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --accent: #4ecdc4;
            --accent2: #ff6b6b;
            --glass-bg: rgba(30,34,45,0.75);
            --main-bg: #181c24;
            --shadow: 0 8px 32px 0 rgba(31,38,135,0.17);
        }
        
        /* Fix for white background during scrolling */
        html {
            background-color: #181c24;
            min-height: 100%;
            overflow-x: hidden;
        }
        
        body {
            background: linear-gradient(120deg, #181c24 0%, #23272f 100%);
            background-attachment: fixed;
            color: #fff;
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #181c24;
            z-index: -2;
        }
        
        .neon-bg {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -1;
            pointer-events: none;
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000px;
        }
        
        .neon-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.4;
            animation: float 8s ease-in-out infinite alternate;
            will-change: transform;
        }
        
        .neon1 { width: 400px; height: 400px; background: #4ecdc4; top: 10%; left: 5%; animation-delay: 0s;}
        .neon2 { width: 300px; height: 300px; background: #ff6b6b; bottom: 10%; right: 8%; animation-delay: 2s;}
        .neon3 { width: 200px; height: 200px; background: #ffe66d; top: 60%; left: 60%; opacity: 0.2; animation-delay: 4s;}
        
        @keyframes float {
            0% { transform: translateY(0) scale(1);}
            100% { transform: translateY(-30px) scale(1.08);}
        }
        
        /* Modern navbar styles */
        nav {
            background: rgba(30,34,45,0.9);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
            padding: 0 20px;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            height: 70px;
        }
        
        .logo {
            font-weight: 700;
            font-size: 1.4rem;
            background: linear-gradient(45deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 1px;
        }
        
        .nav-links {
            display: flex;
            gap: 10px;
        }
        
        .nav-link {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            padding: 8px 16px;
            border-radius: 8px;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: var(--accent);
        }
        
        .nav-link.active {
            background: var(--accent);
            color: #181c24;
        }
        
        .nav-link.filled {
            background: var(--accent2);
            color: #fff;
        }
        
        .nav-link.filled:hover {
            background: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,107,107,0.3);
        }
        
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }
        
        .admin-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .admin-title {
            font-size: 2.2rem;
            margin: 20px 0;
            background: linear-gradient(45deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 12px #23272f80;
            animation: neonText 2s infinite alternate;
        }
        
        @keyframes neonText {
            0% { text-shadow: 0 0 8px var(--accent), 0 2px 12px #23272f80;}
            100% { text-shadow: 0 0 18px var(--accent2), 0 2px 12px #23272f80;}
        }
        
        .admin-subtitle {
            color: #e0f7fa;
            font-size: 1.1rem;
        }
        
        .admin-panel {
            background: var(--glass-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
            border: 1.5px solid rgba(255,255,255,0.10);
            animation: fadeInUp 1.2s cubic-bezier(.23,1.01,.32,1) both;
            will-change: transform, opacity;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(60px);}
            to { opacity: 1; transform: translateY(0);}
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .users-table th, .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .users-table th {
            background: rgba(35, 39, 47, 0.5);
            color: var(--accent);
            font-weight: 600;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
        }
        
        .users-table tr:hover {
            background: rgba(35, 39, 47, 0.3);
        }
        
        .users-table td {
            color: #e0f7fa;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .edit-btn {
            background: var(--accent);
            color: #181c24;
            margin-right: 8px;
        }
        
        .delete-btn {
            background: var(--accent2);
            color: #fff;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78, 205, 196, 0.3);
        }
        
        .delete-btn:hover {
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }
        
        .search-box {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-box input {
            flex-grow: 1;
            padding: 12px 15px;
            border-radius: 10px 0 0 10px;
            border: none;
            background: rgba(35, 39, 47, 0.7);
            color: #fff;
            font-size: 1rem;
            outline: none;
        }
        
        .search-box button {
            padding: 12px 20px;
            border-radius: 0 10px 10px 0;
            border: none;
            background: var(--accent);
            color: #181c24;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-box button:hover {
            background: var(--accent2);
        }
        
        @media (max-width: 768px) {
            .admin-title {
                font-size: 1.8rem;
            }
            
            .admin-panel {
                padding: 20px 15px;
                overflow-x: auto;
            }
            
            .action-btn {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="neon-bg">
        <div class="neon-circle neon1"></div>
        <div class="neon-circle neon2"></div>
        <div class="neon-circle neon3"></div>
    </div>
    
    <nav>
        <div class="nav-container">
            <a href="index.html" class="logo">BZ Admin</a>
            <div class="nav-links">
                <a href="index.html" class="nav-link">Home</a>
                <a href="admin_panel.php" class="nav-link active">Users</a>
                <a href="logout.php" class="nav-link filled">Sign Out</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="admin-header">
            <h1 class="admin-title">Admin Panel</h1>
            <p class="admin-subtitle">Manage registered users</p>
        </div>
        
        <div class="admin-panel">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search users...">
                <button onclick="searchUsers()"><i class="fas fa-search"></i> Search</button>
            </div>
            
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["id"] . "</td>";
                            echo "<td>" . $row["fullname"] . "</td>";
                            echo "<td>" . $row["email"] . "</td>";
                            echo "<td>" . $row["username"] . "</td>";
                            echo "<td>" . $row["created_at"] . "</td>";
                            echo "<td>
                                    <button class='action-btn edit-btn' onclick='editUser(" . $row["id"] . ")'><i class='fas fa-edit'></i> Edit</button>
                                    <button class='action-btn delete-btn' onclick='deleteUser(" . $row["id"] . ")'><i class='fas fa-trash'></i> Delete</button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center;'>No users found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function searchUsers() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const table = document.querySelector('.users-table');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                let found = false;
                const cells = rows[i].getElementsByTagName('td');
                
                for (let j = 0; j < cells.length; j++) {
                    const cellText = cells[j].textContent.toLowerCase();
                    
                    if (cellText.indexOf(input) > -1) {
                        found = true;
                        break;
                    }
                }
                
                if (found) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
        
        function editUser(userId) {
            // Redirect to edit user page or open modal
            alert('Edit user with ID: ' + userId);
            // Implementation for editing user
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                // Send AJAX request to delete user
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=' + userId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('User deleted successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
    </script>
</body>
</html> 