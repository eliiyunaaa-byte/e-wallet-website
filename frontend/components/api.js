// API Helper for E-Wallet Frontend
// All API calls go through this helper

const API_BASE_URL = '/backend/service/api.php';

class EWalletAPI {
    /**
     * Make API call
     */
    static async call(action, method = 'GET', data = null) {
        try {
            const url = new URL(API_BASE_URL, window.location.origin);
            url.searchParams.append('action', action);

            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };

            if (method === 'POST' && data) {
                options.body = JSON.stringify(data);
            } else if (method === 'GET' && data) {
                Object.keys(data).forEach(key => {
                    url.searchParams.append(key, data[key]);
                });
            }

            console.log('API Request:', url.toString(), options);

            const response = await fetch(url.toString(), options);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const text = await response.text();
            console.log('API Response:', text);

            // Try to parse as JSON
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', text);
                return {
                    status: 'error',
                    message: 'Server returned invalid JSON. Make sure XAMPP Apache is running and PHP is enabled.'
                };
            }
        } catch (error) {
            console.error('API Error:', error);
            return {
                status: 'error',
                message: 'Network error. Make sure XAMPP Apache is running on port 80.'
            };
        }
    }

    /**
     * Login student
     */
    static async login(school_id, password) {
        return this.call('login', 'POST', {
            school_id: school_id,
            password: password
        });
    }

    /**
     * Get student balance
     */
    static async getBalance(student_id) {
        return this.call('get_balance', 'GET', {
            student_id: student_id
        });
    }

    /**
     * Get student transactions
     */
    static async getTransactions(student_id, limit = 50, offset = 0) {
        return this.call('get_transactions', 'GET', {
            student_id: student_id,
            limit: limit,
            offset: offset
        });
    }

    /**
     * Process purchase
     */
    static async processPurchase(student_id, amount, item_name, location) {
        return this.call('process_purchase', 'POST', {
            student_id: student_id,
            amount: amount,
            item_name: item_name,
            location: location
        });
    }

    /**
     * Request cash-in
     */
    static async requestCashIn(student_id, amount, reference_number = null) {
        return this.call('request_cashin', 'POST', {
            student_id: student_id,
            amount: amount,
            reference_number: reference_number
        });
    }

    /**
     * Get weekly spending
     */
    static async getWeeklySpending(student_id) {
        return this.call('get_weekly_spending', 'GET', {
            student_id: student_id
        });
    }

    /**
     * Create PayMongo payment link
     */
    static async createPaymentLink(student_id, amount) {
        return this.call('create_payment_link', 'POST', {
            student_id: student_id,
            amount: amount
        });
    }

    /**
     * Save user session to localStorage
     */
    static saveSession(user_data) {
        localStorage.setItem('student_id', user_data.student_id);
        localStorage.setItem('school_id', user_data.school_id);
        localStorage.setItem('userName', user_data.name);
        localStorage.setItem('gradeSection', user_data.grade_section);
        localStorage.setItem('userID', user_data.school_id);
    }

    /**
     * Get user session from localStorage
     */
    static getSession() {
        return {
            student_id: localStorage.getItem('student_id'),
            school_id: localStorage.getItem('school_id'),
            name: localStorage.getItem('userName'),
            grade_section: localStorage.getItem('gradeSection')
        };
    }

    /**
     * Clear user session
     */
    static clearSession() {
        localStorage.clear();
    }

    /**
     * Check if user is logged in
     */
    static isLoggedIn() {
        return !!localStorage.getItem('student_id');
    }

    /**
     * Request password reset (send OTP)
     */
    static async requestPasswordReset(schoolId) {
        return await this.call('request_password_reset', 'POST', {
            school_id: schoolId
        });
    }

    /**
     * Verify OTP
     */
    static async verifyOTP(schoolId, otp) {
        return await this.call('verify_otp', 'POST', {
            school_id: schoolId,
            otp: otp
        });
    }

    /**
     * Reset password
     */
    static async resetPassword(schoolId, newPassword) {
        return await this.call('reset_password', 'POST', {
            school_id: schoolId,
            new_password: newPassword
        });
    }
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EWalletAPI;
}
