// Mock Data for Ravenhill Coffee Shop Management System

const APP_DATA = {
    roles: [
        { id: 1, name: 'Admin' },
        { id: 2, name: 'Manager' },
        { id: 3, name: 'Cashier' },
        { id: 4, name: 'Barista' },
        { id: 5, name: 'Wait Staff' }
    ],
    currentUser: {
        id: 101,
        name: 'Sarah Connor',
        role: 'Admin',
        avatar: 'SC'
    },
    categories: [
        { id: 1, name: 'Espresso', icon: '☕' },
        { id: 2, name: 'Filter', icon: '🫖' },
        { id: 3, name: 'Iced', icon: '🧊' },
        { id: 4, name: 'Tea', icon: '🍵' },
        { id: 5, name: 'Pastries', icon: '🥐' },
        { id: 6, name: 'Merch', icon: '👕' }
    ],
    products: [
        { id: 1, categoryId: 1, name: 'Flat White', price: 4.50, image: 'https://images.unsplash.com/photo-1577003833214-3d02a0a20e48?w=300&h=300&fit=crop', available: true },
        { id: 2, categoryId: 1, name: 'Latte', price: 4.50, image: 'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?w=300&h=300&fit=crop', available: true },
        { id: 3, categoryId: 1, name: 'Cappuccino', price: 4.50, image: 'https://images.unsplash.com/photo-1534778101976-62847782c213?w=300&h=300&fit=crop', available: true },
        { id: 4, categoryId: 1, name: 'Long Black', price: 4.00, image: 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?w=300&h=300&fit=crop', available: true },
        { id: 5, categoryId: 2, name: 'Batch Brew', price: 5.00, image: 'https://images.unsplash.com/photo-1611162458324-aae1eb4129a4?w=300&h=300&fit=crop', available: true },
        { id: 6, categoryId: 2, name: 'Pour Over', price: 7.00, image: 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=300&h=300&fit=crop', available: true },
        { id: 7, categoryId: 3, name: 'Iced Latte', price: 5.50, image: 'https://images.unsplash.com/photo-1517701550927-30cf0b63f520?w=300&h=300&fit=crop', available: true },
        { id: 8, categoryId: 5, name: 'Almond Croissant', price: 6.50, image: 'https://images.unsplash.com/photo-1555507036-ab1e4006aaeb?w=300&h=300&fit=crop', available: true },
        { id: 9, categoryId: 5, name: 'Banana Bread', price: 5.50, image: 'https://images.unsplash.com/photo-1601000938259-9e92002320b2?w=300&h=300&fit=crop', available: true }
    ],
    customizations: [
        { id: 'milk', name: 'Milk Type', options: [
            { id: 'regular', name: 'Regular Milk', price: 0 },
            { id: 'oat', name: 'Oat Milk', price: 0.80 },
            { id: 'soy', name: 'Soy Milk', price: 0.80 },
            { id: 'almond', name: 'Almond Milk', price: 0.80 }
        ]},
        { id: 'size', name: 'Size', options: [
            { id: 'regular', name: 'Regular', price: 0 },
            { id: 'large', name: 'Large', price: 1.00 }
        ]},
        { id: 'extras', name: 'Extras', options: [
            { id: 'extra_shot', name: 'Extra Shot', price: 0.50 },
            { id: 'vanilla', name: 'Vanilla Syrup', price: 0.50 },
            { id: 'caramel', name: 'Caramel Syrup', price: 0.50 },
            { id: 'decaf', name: 'Decaf', price: 0 }
        ]}
    ],
    tables: [
        { id: 1, number: '1', capacity: 2, status: 'available', x: 100, y: 100, w: 60, h: 60 },
        { id: 2, number: '2', capacity: 2, status: 'occupied', x: 200, y: 100, w: 60, h: 60 },
        { id: 3, number: '3', capacity: 4, status: 'available', x: 350, y: 100, w: 100, h: 60 },
        { id: 4, number: '4', capacity: 4, status: 'reserved', x: 100, y: 250, w: 100, h: 60 },
        { id: 5, number: '5', capacity: 6, status: 'available', x: 300, y: 250, w: 150, h: 80 },
        { id: 6, number: 'W1', capacity: 1, status: 'occupied', x: 100, y: 400, w: 40, h: 40 },
        { id: 7, number: 'W2', capacity: 1, status: 'available', x: 160, y: 400, w: 40, h: 40 }
    ],
    activeOrders: [
        {
            id: 'ORD-1001',
            status: 'pending', /* pending, brewing, ready, served */
            time: '10:42 AM',
            type: 'Dine In (Table 2)',
            items: [
                { name: 'Flat White', qty: 1, mods: ['Oat Milk'] },
                { name: 'Almond Croissant', qty: 1, mods: [] }
            ]
        },
        {
            id: 'ORD-1002',
            status: 'brewing',
            time: '10:40 AM',
            type: 'Takeaway',
            items: [
                { name: 'Latte', qty: 2, mods: ['Large', 'Extra Shot'] }
            ]
        },
        {
            id: 'ORD-1003',
            status: 'ready',
            time: '10:35 AM',
            type: 'Takeaway',
            items: [
                { name: 'Long Black', qty: 1, mods: [] },
                { name: 'Banana Bread', qty: 1, mods: ['Toasted'] }
            ]
        }
    ],
    dashboardStats: {
        dailySales: '$2,450.50',
        salesTrend: '+12%',
        ordersToday: 145,
        ordersTrend: '+5%',
        avgWaitTime: '4.2 min',
        waitTrend: '-1 min',
        topSelling: 'Flat White'
    }
};
