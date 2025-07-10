import { Router, Request, Response } from 'express';
import { TemplateService } from '../services/template.service';
import fs from 'fs/promises';
import path from 'path';

const router = Router();

// 홈페이지
router.get('/', async (req: Request, res: Response) => {
  try {
    const [header, footer] = await Promise.all([
      TemplateService.loadInclude('header.html'),
      TemplateService.loadInclude('footer.html')
    ]);
    
    // index.html 템플릿 읽기
    const indexPath = path.join(__dirname, '../../templates/index.html');
    let indexContent = await fs.readFile(indexPath, 'utf-8');
    
    // includes 처리
    indexContent = indexContent.replace('<!-- include:header -->', header);
    indexContent = indexContent.replace('<!-- include:footer -->', footer);
    
    res.send(indexContent);
  } catch (error) {
    console.error('홈페이지 로드 오류:', error);
    res.status(500).send('서버 오류가 발생했습니다.');
  }
});

// 회사소개 페이지
router.get('/about.html', async (req: Request, res: Response) => {
  try {
    const [header, footer] = await Promise.all([
      TemplateService.loadInclude('header.html'),
      TemplateService.loadInclude('footer.html')
    ]);
    
    // about.html 템플릿 읽기
    const aboutPath = path.join(__dirname, '../../templates/about.html');
    let aboutContent = await fs.readFile(aboutPath, 'utf-8');
    
    // includes 처리
    aboutContent = aboutContent.replace('<!-- include:header -->', header);
    aboutContent = aboutContent.replace('<!-- include:footer -->', footer);
    
    res.send(aboutContent);
  } catch (error) {
    console.error('회사소개 페이지 로드 오류:', error);
    res.status(500).send('서버 오류가 발생했습니다.');
  }
});

export default router;