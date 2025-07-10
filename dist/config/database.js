"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.pool = void 0;
exports.testConnection = testConnection;
const promise_1 = __importDefault(require("mysql2/promise"));
const dotenv_1 = __importDefault(require("dotenv"));
dotenv_1.default.config();
const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    port: parseInt(process.env.DB_PORT || '3306'),
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'chungnamsteel',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};
exports.pool = promise_1.default.createPool(dbConfig);
async function testConnection() {
    try {
        const connection = await exports.pool.getConnection();
        console.log('데이터베이스 연결 성공');
        connection.release();
        return true;
    }
    catch (error) {
        console.error('데이터베이스 연결 실패:', error);
        return false;
    }
}
//# sourceMappingURL=database.js.map