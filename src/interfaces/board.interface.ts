export interface Board {
  id: number;
  title: string;
  content: string;
  author: string;
  created_at: Date;
  updated_at: Date;
  view_count: number;
  board_type: 'quote' | 'notice' | 'news';
}

export interface Quote extends Board {
  company_name: string;
  contact_email: string;
  contact_phone: string;
  product_type: string;
  quantity: string;
  desired_date: Date;
  status: 'pending' | 'processing' | 'completed';
}

export interface Notice extends Board {
  is_important: boolean;
  attachment_url?: string;
}

export interface News extends Board {
  source: string;
  external_url?: string;
  thumbnail_url?: string;
}