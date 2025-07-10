"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const template_service_1 = require("../services/template.service");
const promises_1 = __importDefault(require("fs/promises"));
const path_1 = __importDefault(require("path"));
const router = (0, express_1.Router)();
// 홈페이지
router.get('/', async (req, res) => {
    try {
        const [header, footer] = await Promise.all([
            template_service_1.TemplateService.loadInclude('header.html'),
            template_service_1.TemplateService.loadInclude('footer.html')
        ]);
        // index.html 템플릿 읽기
        const indexPath = path_1.default.join(__dirname, '../../templates/index.html');
        let indexContent = await promises_1.default.readFile(indexPath, 'utf-8');
        // includes 처리
        indexContent = indexContent.replace('<!-- include:header -->', header);
        indexContent = indexContent.replace('<!-- include:footer -->', footer);
        res.send(indexContent);
    }
    catch (error) {
        console.error('홈페이지 로드 오류:', error);
        res.status(500).send('서버 오류가 발생했습니다.');
    }
});
// 회사소개 페이지
router.get('/about.html', async (req, res) => {
    try {
        const [header, footer] = await Promise.all([
            template_service_1.TemplateService.loadInclude('header.html'),
            template_service_1.TemplateService.loadInclude('footer.html')
        ]);
        // about.html 템플릿 읽기
        const aboutPath = path_1.default.join(__dirname, '../../templates/about.html');
        let aboutContent = await promises_1.default.readFile(aboutPath, 'utf-8');
        // includes 처리
        aboutContent = aboutContent.replace('<!-- include:header -->', header);
        aboutContent = aboutContent.replace('<!-- include:footer -->', footer);
        res.send(aboutContent);
    }
    catch (error) {
        console.error('회사소개 페이지 로드 오류:', error);
        res.status(500).send('서버 오류가 발생했습니다.');
    }
});
exports.default = router;
//# sourceMappingURL=page.routes.js.map