"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = __importDefault(require("express"));
const cors_1 = __importDefault(require("cors"));
const path_1 = __importDefault(require("path"));
const dotenv_1 = __importDefault(require("dotenv"));
const board_routes_1 = __importDefault(require("./routes/board.routes"));
const api_routes_1 = __importDefault(require("./routes/api.routes"));
const page_routes_1 = __importDefault(require("./routes/page.routes"));
const database_1 = require("./config/database");
dotenv_1.default.config();
const app = (0, express_1.default)();
const PORT = process.env.PORT || 1112;
const HOST = process.env.HOST || '192.168.1.251';
app.use((0, cors_1.default)());
app.use(express_1.default.json());
app.use(express_1.default.urlencoded({ extended: true }));
// Static files - serve from root directory
app.use(express_1.default.static(path_1.default.join(__dirname, '..'), {
    index: false // index.html을 자동으로 서빙하지 않음
}));
// Routes
app.use(page_routes_1.default);
app.use(board_routes_1.default);
app.use(api_routes_1.default);
// Database connection test and server start
async function startServer() {
    const dbConnected = await (0, database_1.testConnection)();
    if (!dbConnected) {
        console.warn('데이터베이스 연결 실패 - 서버는 계속 실행됩니다.');
    }
    app.listen(PORT, HOST, () => {
        console.log(`충남스틸 웹사이트가 http://${HOST}:${PORT} 에서 실행 중입니다`);
    });
}
startServer();
//# sourceMappingURL=server.js.map