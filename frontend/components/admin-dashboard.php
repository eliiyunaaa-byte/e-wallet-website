<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Siena College E-Wallet</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-gray-100">
    
    <!-- Header -->
    <header class="bg-gradient-to-r from-red-900 to-red-700 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Admin Dashboard</h1>
                    <p class="text-sm text-gray-200">Siena College E-Wallet Management</p>
                </div>
                <div class="flex items-center gap-4">
                    <span id="adminName" class="text-sm"></span>
                    <button onclick="logout()" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-600 text-sm">Total Students</h3>
                <p id="totalStudents" class="text-3xl font-bold text-red-900">0</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-600 text-sm">Active Students</h3>
                <p id="activeStudents" class="text-3xl font-bold text-green-600">0</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-gray-600 text-sm">Total Balance</h3>
                <p id="totalBalance" class="text-3xl font-bold text-yellow-600">₱0.00</p>
            </div>
        </div>

        <!-- Students Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold">Students Management</h2>
                <button onclick="openAddModal()" class="bg-red-900 hover:bg-red-800 text-white px-4 py-2 rounded">
                    + Add Student
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">School ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade/Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Balance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTable" class="divide-y divide-gray-200">
                        <!-- Students will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add/Edit Student Modal -->
    <div id="studentModal" class="modal">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <h3 id="modalTitle" class="text-xl font-bold mb-4">Add Student</h3>
            <form id="studentForm">
                <input type="hidden" id="studentId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">School ID</label>
                    <input type="text" id="schoolId" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" id="firstName" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input type="text" id="lastName" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="tel" id="phone" placeholder="09xxxxxxxxx" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grade & Section</label>
                    <input type="text" id="gradeSection" placeholder="10-Eucharist Centered" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" placeholder="Leave blank to keep current" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Initial Balance</label>
                    <input type="number" id="balance" value="0" step="0.01" min="0" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-900">
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-900 text-white rounded-lg hover:bg-red-800">
                        Save Student
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="api.js"></script>
    <script>
        // Check if admin is logged in
        if (!localStorage.getItem('admin_id')) {
            window.location.href = 'index.php';
        }

        // Display admin name
        document.getElementById('adminName').textContent = 'Admin: ' + localStorage.getItem('admin_username');

        // Load students on page load
        loadStudents();

        async function loadStudents() {
            try {
                const response = await fetch('/e-wallet-website/e-wallet-website/backend/service/admin-api.php?action=get_students');
                const result = await response.json();

                if (result.status === 'success') {
                    displayStudents(result.students);
                    updateStats(result.students);
                }
            } catch (error) {
                console.error('Error loading students:', error);
                alert('Failed to load students');
            }
        }

        function displayStudents(students) {
            const tbody = document.getElementById('studentsTable');
            tbody.innerHTML = '';

            students.forEach(student => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-6 py-4">${student.school_id}</td>
                    <td class="px-6 py-4">${student.full_name}</td>
                    <td class="px-6 py-4">${student.email || 'N/A'}</td>
                    <td class="px-6 py-4">${student.grade_section || 'N/A'}</td>
                    <td class="px-6 py-4">₱${parseFloat(student.balance).toFixed(2)}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded ${student.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${student.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <button onclick="editStudent(${student.student_id})" class="text-blue-600 hover:underline mr-2">Edit</button>
                        <button onclick="deleteStudent(${student.student_id}, '${student.full_name}')" class="text-red-600 hover:underline">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        function updateStats(students) {
            document.getElementById('totalStudents').textContent = students.length;
            document.getElementById('activeStudents').textContent = students.filter(s => s.is_active).length;
            
            const totalBalance = students.reduce((sum, s) => sum + parseFloat(s.balance), 0);
            document.getElementById('totalBalance').textContent = '₱' + totalBalance.toFixed(2);
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Student';
            document.getElementById('studentForm').reset();
            document.getElementById('studentId').value = '';
            document.getElementById('password').required = true;
            document.getElementById('studentModal').classList.add('active');
        }

        async function editStudent(studentId) {
            try {
                const response = await fetch(`/e-wallet-website/e-wallet-website/backend/service/admin-api.php?action=get_student&student_id=${studentId}`);
                const result = await response.json();

                if (result.status === 'success') {
                    const student = result.student;
                    document.getElementById('modalTitle').textContent = 'Edit Student';
                    document.getElementById('studentId').value = student.student_id;
                    document.getElementById('schoolId').value = student.school_id;
                    document.getElementById('firstName').value = student.first_name;
                    document.getElementById('lastName').value = student.last_name;
                    document.getElementById('email').value = student.email || '';
                    document.getElementById('phone').value = student.phone || '';
                    document.getElementById('gradeSection').value = student.grade_section || '';
                    document.getElementById('balance').value = student.balance;
                    document.getElementById('password').required = false;
                    document.getElementById('studentModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error loading student:', error);
                alert('Failed to load student details');
            }
        }

        async function deleteStudent(studentId, studentName) {
            if (!confirm(`Are you sure you want to delete ${studentName}?`)) {
                return;
            }

            try {
                const response = await fetch('/e-wallet-website/e-wallet-website/backend/service/admin-api.php?action=delete_student', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_id: studentId })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert('Student deleted successfully');
                    loadStudents();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error deleting student:', error);
                alert('Failed to delete student');
            }
        }

        document.getElementById('studentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const studentId = document.getElementById('studentId').value;
            const formData = {
                school_id: document.getElementById('schoolId').value,
                first_name: document.getElementById('firstName').value,
                last_name: document.getElementById('lastName').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                grade_section: document.getElementById('gradeSection').value,
                balance: document.getElementById('balance').value,
                password: document.getElementById('password').value
            };

            if (studentId) {
                formData.student_id = studentId;
            }

            const action = studentId ? 'update_student' : 'create_student';

            try {
                const response = await fetch(`/e-wallet-website/e-wallet-website/backend/service/admin-api.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert(studentId ? 'Student updated successfully' : 'Student created successfully');
                    closeModal();
                    loadStudents();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error saving student:', error);
                alert('Failed to save student');
            }
        });

        function closeModal() {
            document.getElementById('studentModal').classList.remove('active');
        }

        function logout() {
            localStorage.clear();
            window.location.href = 'index.php';
        }
    </script>

</body>
</html>
