"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const board_service_1 = require("../services/board.service");
const router = (0, express_1.Router)();
// 견적문의 제출
router.post('/api/quote', async (req, res) => {
    try {
        const result = await board_service_1.BoardService.createQuote(req.body);
        res.json({ success: true, message: '견적문의가 성공적으로 접수되었습니다.' });
    }
    catch (error) {
        console.error('견적문의 처리 오류:', error);
        res.status(500).json({ success: false, message: '견적문의 처리 중 오류가 발생했습니다.' });
    }
});
// 게시글 목록 API
router.get('/api/boards/:boardType', async (req, res) => {
    try {
        const { boardType } = req.params;
        const limit = parseInt(req.query.limit) || 10;
        const offset = parseInt(req.query.offset) || 0;
        const boards = await board_service_1.BoardService.getBoards(boardType, limit, offset);
        res.json({ success: true, data: boards });
    }
    catch (error) {
        console.error('게시글 목록 조회 오류:', error);
        res.status(500).json({ success: false, message: '게시글 목록 조회 중 오류가 발생했습니다.' });
    }
});
// 게시글 상세 API
router.get('/api/board/:id', async (req, res) => {
    try {
        const boardId = parseInt(req.params.id);
        const board = await board_service_1.BoardService.getBoardById(boardId);
        if (!board) {
            return res.status(404).json({ success: false, message: '게시글을 찾을 수 없습니다.' });
        }
        res.json({ success: true, data: board });
    }
    catch (error) {
        console.error('게시글 상세 조회 오류:', error);
        res.status(500).json({ success: false, message: '게시글 조회 중 오류가 발생했습니다.' });
    }
});
exports.default = router;
//# sourceMappingURL=api.routes.js.map