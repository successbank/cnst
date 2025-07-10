import express from 'express';
import cors from 'cors';
import path from 'path';
import dotenv from 'dotenv';
import boardRoutes from './routes/board.routes';
import apiRoutes from './routes/api.routes';
import pageRoutes from './routes/page.routes';
import { testConnection } from './config/database';

dotenv.config();

const app = express();
const PORT = process.env.PORT || 1112;
const HOST = process.env.HOST || '192.168.1.251';

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Static files - serve from root directory
app.use(express.static(path.join(__dirname, '..'), {
  index: false // index.html을 자동으로 서빙하지 않음
}));

// Routes
app.use(pageRoutes);
app.use(boardRoutes);
app.use(apiRoutes);

// Database connection test and server start
async function startServer() {
  const dbConnected = await testConnection();
  
  if (!dbConnected) {
    console.warn('데이터베이스 연결 실패 - 서버는 계속 실행됩니다.');
  }
  
  app.listen(PORT as number, HOST, () => {
    console.log(`충남스틸 웹사이트가 http://${HOST}:${PORT} 에서 실행 중입니다`);
  });
}

startServer();