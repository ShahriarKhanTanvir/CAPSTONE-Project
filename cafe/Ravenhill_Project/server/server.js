const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');
const path = require('path');
const { initDatabase } = require('./db/database');

const menuRoutes = require('./routes/menu.routes');
const ordersRoutes = require('./routes/orders.routes');
const inventoryRoutes = require('./routes/inventory.routes');
const tablesRoutes = require('./routes/tables.routes');
const staffRoutes = require('./routes/staff.routes');
const analyticsRoutes = require('./routes/analytics.routes');

const reservationsRoutes = require('./routes/reservations.routes');
const customersRoutes = require('./routes/customers.routes');
const transactionsRoutes = require('./routes/transactions.routes');
const discountsRoutes = require('./routes/discounts.routes');
const feedbackRoutes = require('./routes/feedback.routes');
const stateRoutes = require('./routes/state.routes');
const auditRoutes = require('./routes/audit.routes');

const app = express();
const server = http.createServer(app);

// Initialize Socket.io with CORS
const io = new Server(server, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST', 'PATCH', 'PUT', 'DELETE']
  }
});

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Attach socket.io instance to Express app
app.set('io', io);

// Serve static frontend files from project root
app.use(express.static(path.join(__dirname, '..')));

// API Routes Mount
app.use('/api/menu', menuRoutes);
app.use('/api/orders', ordersRoutes);
app.use('/api/inventory', inventoryRoutes);
app.use('/api/tables', tablesRoutes);
app.use('/api/staff', staffRoutes);
app.use('/api/analytics', analyticsRoutes);
app.use('/api/reservations', reservationsRoutes);
app.use('/api/customers', customersRoutes);
app.use('/api/transactions', transactionsRoutes);
app.use('/api/discounts', discountsRoutes);
app.use('/api/feedback', feedbackRoutes);
app.use('/api/state', stateRoutes);
app.use('/api/audit', auditRoutes);


// Health Check Endpoint
app.get('/api/health', (req, res) => {
  res.json({
    status: 'online',
    service: 'Ravenhill Coffee POS & Management API Engine',
    timestamp: new Date().toISOString()
  });
});

// Socket.io Real-Time Event Handlers
io.on('connection', (socket) => {
  console.log(`[Socket.io] Client connected: ${socket.id}`);

  socket.on('disconnect', () => {
    console.log(`[Socket.io] Client disconnected: ${socket.id}`);
  });
});

// Initialize DB and Start Server
const PORT = process.env.PORT || 5000;

initDatabase()
  .then(() => {
    server.listen(PORT, () => {
      console.log(`=======================================================`);
      console.log(` Ravenhill Coffee POS Backend Engine is running!`);
      console.log(` REST API URL: http://localhost:${PORT}/api/health`);
      console.log(` Socket.io Stream: ws://localhost:${PORT}`);
      console.log(`=======================================================`);
    });
  })
  .catch((err) => {
    console.error('Failed to initialize database and launch server:', err);
  });
