import { Router, Request, Response } from 'express';
import { BoardService } from '../services/board.service';

const router = Router();

// 견적문의 제출
router.post('/api/quote', async (req: Request, res: Response) => {
  try {
    const result = await BoardService.createQuote(req.body);
    res.json({ success: true, message: '견적문의가 성공적으로 접수되었습니다.' });
  } catch (error) {
    console.error('견적문의 처리 오류:', error);
    res.status(500).json({ success: false, message: '견적문의 처리 중 오류가 발생했습니다.' });
  }
});

// 게시글 목록 API
router.get('/api/boards/:boardType', async (req: Request, res: Response) => {
  try {
    const { boardType } = req.params;
    const limit = parseInt(req.query.limit as string) || 10;
    const offset = parseInt(req.query.offset as string) || 0;
    
    const boards = await BoardService.getBoards(boardType, limit, offset);
    res.json({ success: true, data: boards });
  } catch (error) {
    console.error('게시글 목록 조회 오류:', error);
    res.status(500).json({ success: false, message: '게시글 목록 조회 중 오류가 발생했습니다.' });
  }
});

// 게시글 상세 API
router.get('/api/board/:id', async (req: Request, res: Response) => {
  try {
    const boardId = parseInt(req.params.id);
    const board = await BoardService.getBoardById(boardId);
    
    if (!board) {
      return res.status(404).json({ success: false, message: '게시글을 찾을 수 없습니다.' });
    }
    
    res.json({ success: true, data: board });
  } catch (error) {
    console.error('게시글 상세 조회 오류:', error);
    res.status(500).json({ success: false, message: '게시글 조회 중 오류가 발생했습니다.' });
  }
});

export default router;