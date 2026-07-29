/**
 * Company Features Module
 * Διαχείριση των interactive features για τις εταιρείες
 */

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import Chart from 'chart.js/auto';

// Alpine.js Components
document.addEventListener('alpine:init', () => {
    // Fleet Management Component
    Alpine.data('fleetManagement', () => ({
        showDetails: false,
        vehicles: [],
        loading: false,
        
        async loadVehicles() {
            this.loading = true;
            try {
                const response = await fetch('/api/company/fleet/vehicles');
                this.vehicles = await response.json();
            } catch (error) {
                console.error('Error loading vehicles:', error);
            } finally {
                this.loading = false;
            }
        },
        
        toggleDetails() {
            this.showDetails = !this.showDetails;
            if (this.showDetails && this.vehicles.length === 0) {
                this.loadVehicles();
            }
        }
    }));
    
    // Driver Management Component
    Alpine.data('driverManagement', () => ({
        activeTab: 'overview',
        stats: {
            total: 0,
            active: 0,
            onDuty: 0,
            available: 0
        },
        
        setTab(tab) {
            this.activeTab = tab;
        },
        
        async loadStats() {
            try {
                const response = await fetch('/api/company/drivers/stats');
                this.stats = await response.json();
            } catch (error) {
                console.error('Error loading driver stats:', error);
            }
        }
    }));
    
    // Subscription Management
    Alpine.data('subscriptionManager', () => ({
        currentPlan: 'basic',
        showUpgradeModal: false,
        
        async upgradePlan(newPlan) {
            const result = await Swal.fire({
                title: 'Αναβάθμιση Πακέτου',
                text: `Θέλετε να αναβαθμίσετε στο πακέτο ${newPlan};`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ναι, αναβάθμιση',
                cancelButtonText: 'Ακύρωση',
                confirmButtonColor: '#aa3636'
            });
            
            if (result.isConfirmed) {
                try {
                    const response = await fetch('/api/company/subscription/upgrade', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ plan: newPlan })
                    });
                    
                    if (response.ok) {
                        Swal.fire({
                            title: 'Επιτυχία!',
                            text: 'Το πακέτο σας αναβαθμίστηκε επιτυχώς',
                            icon: 'success',
                            confirmButtonColor: '#aa3636'
                        });
                        this.currentPlan = newPlan;
                    }
                } catch (error) {
                    Swal.fire({
                        title: 'Σφάλμα',
                        text: 'Υπήρξε πρόβλημα με την αναβάθμιση',
                        icon: 'error',
                        confirmButtonColor: '#aa3636'
                    });
                }
            }
        }
    }));
});

// Charts για Analytics
export function initializeCharts() {
    // Fleet Utilization Chart
    const fleetCtx = document.getElementById('fleetUtilizationChart');
    if (fleetCtx) {
        new Chart(fleetCtx, {
            type: 'doughnut',
            data: {
                labels: ['Σε Χρήση', 'Διαθέσιμα', 'Συντήρηση'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: [
                        '#10b981',
                        '#3b82f6',
                        '#ef4444'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
    
    // Driver Performance Chart
    const driverCtx = document.getElementById('driverPerformanceChart');
    if (driverCtx) {
        new Chart(driverCtx, {
            type: 'bar',
            data: {
                labels: ['Ιαν', 'Φεβ', 'Μαρ', 'Απρ', 'Μαι', 'Ιουν'],
                datasets: [{
                    label: 'Μέση Απόδοση',
                    data: [85, 88, 82, 90, 92, 89],
                    backgroundColor: '#aa3636'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
}

// Interactive Features
export function initializeInteractiveFeatures() {
    // Smooth scroll to sections
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Lazy loading for images
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// Real-time Updates με WebSocket
export function initializeRealTimeUpdates() {
    if ('WebSocket' in window) {
        const ws = new WebSocket('wss://your-websocket-server.com');
        
        ws.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            // Update UI based on real-time data
            if (data.type === 'fleet_update') {
                updateFleetStatus(data.payload);
            } else if (data.type === 'driver_status') {
                updateDriverStatus(data.payload);
            }
        };
    }
}

function updateFleetStatus(data) {
    // Update fleet cards with real-time data
    const fleetCard = document.querySelector('[data-fleet-id="' + data.vehicleId + '"]');
    if (fleetCard) {
        fleetCard.querySelector('.status').textContent = data.status;
        fleetCard.querySelector('.location').textContent = data.location;
    }
}

function updateDriverStatus(data) {
    // Update driver cards with real-time data
    const driverCard = document.querySelector('[data-driver-id="' + data.driverId + '"]');
    if (driverCard) {
        driverCard.querySelector('.status').textContent = data.status;
        driverCard.querySelector('.last-update').textContent = 'Μόλις τώρα';
    }
}

// Export για χρήση σε άλλα modules
export default {
    initializeCharts,
    initializeInteractiveFeatures,
    initializeRealTimeUpdates
};
