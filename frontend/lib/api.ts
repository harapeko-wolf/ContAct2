import axios from 'axios';
import Cookies from 'js-cookie';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
});

// リクエストインターセプター
api.interceptors.request.use((config) => {
  const token = Cookies.get('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// レスポンスインターセプター
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      Cookies.remove('token');
      window.location.href = '/admin/login';
    }
    return Promise.reject(error);
  }
);

export interface Company {
  id: string;
  name: string;
  email: string;
  phone?: string;
  address?: string;
  website?: string;
  description?: string;
  industry?: string;
  employee_count?: number;
  status: 'active' | 'considering' | 'inactive';
  created_at: string;
  updated_at: string;
}

export interface PaginatedResponse<T> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number;
  last_page: number;
  last_page_url: string;
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}

export const companyApi = {
  getAll: (page: number = 1, perPage: number = 10) => 
    api.get<PaginatedResponse<Company>>(`/companies?page=${page}&per_page=${perPage}`).then((res) => res.data),
  get: (id: string) => api.get<Company>(`/companies/${id}`).then((res) => res.data),
  create: (data: Partial<Company>) => api.post<Company>('/companies', data).then((res) => res.data),
  update: (id: string, data: Partial<Company>) => api.put<Company>(`/companies/${id}`, data).then((res) => res.data),
  delete: (id: string) => api.delete(`/companies/${id}`).then((res) => res.data),
};

export interface PDF {
  id: string;
  title: string;
  file_path: string;
  file_name: string;
  file_size: number;
  mime_type: string;
  status: 'active' | 'inactive';
  sort_order?: number;
  metadata: {
    original_name: string;
    uploaded_by: string;
    environment: string;
  };
  created_at: string;
  updated_at: string;
}

export const pdfApi = {
  // PDF一覧を取得（管理画面用）
  getAll: (companyId: string) => 
    api.get<PaginatedResponse<PDF>>(`/admin/companies/${companyId}/pdfs`).then((res) => res.data),

  // PDF一覧を取得（公開用）
  getPublicAll: async (companyId: string) => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api'}/public/companies/${companyId}/pdfs`);
    if (!response.ok) {
      throw new Error('PDF一覧の取得に失敗しました');
    }
    return response.json();
  },

  // PDF詳細を取得
  get: (companyId: string, pdfId: string) => 
    api.get<PDF>(`/admin/companies/${companyId}/pdfs/${pdfId}`).then((res) => res.data),

  // PDFをアップロード
  upload: (companyId: string, formData: FormData) => 
    api.post<PDF>(`/admin/companies/${companyId}/pdfs`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }).then((res) => res.data),

  // PDFを更新
  update: (companyId: string, pdfId: string, data: Partial<PDF>) => 
    api.put<PDF>(`/admin/companies/${companyId}/pdfs/${pdfId}`, data).then((res) => res.data),

  // PDFを削除
  delete: (companyId: string, pdfId: string) => 
    api.delete(`/admin/companies/${companyId}/pdfs/${pdfId}`).then((res) => res.data),

  // プレビューURLを取得
  getPreviewUrl: (companyId: string, pdfId: string) => 
    `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api'}/admin/companies/${companyId}/pdfs/${pdfId}/preview`,

  // 公開プレビューURLを取得
  getPublicPreviewUrl: (companyId: string, pdfId: string) => 
    `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api'}/public/companies/${companyId}/pdfs/${pdfId}/preview`,

  // ダウンロードURLを取得
  getDownloadUrl: (companyId: string, pdfId: string) => 
    `${process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api'}/admin/companies/${companyId}/pdfs/${pdfId}/download`,

  // ドキュメント閲覧ログを記録
  logView: async (companyId: string, documentId: string, data: {
    page_number: number;
    view_duration: number;
    viewer_ip?: string;
    viewer_user_agent?: string;
  }) => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api'}/companies/${companyId}/pdfs/${documentId}/view-logs`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    if (!response.ok) throw new Error('ログ記録に失敗しました');
    return response.json();
  },

  // ドキュメント閲覧ログを取得
  getViewLogs: async (companyId: string, documentId: string) => {
    const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api'}/companies/${companyId}/pdfs/${documentId}/view-logs`);
    if (!response.ok) throw new Error('ログ取得に失敗しました');
    return response.json();
  },

  // 会社の全閲覧ログを取得（管理画面用）
  getCompanyViewLogs: (companyId: string) => 
    api.get(`/admin/companies/${companyId}/view-logs`).then((res) => res.data),

  // PDF並び順更新
  updateSortOrder: async (companyId: string, documents: { id: string; sort_order: number }[]) => {
    return api.put(`/admin/companies/${companyId}/pdfs/sort-order`, { documents })
      .then((res) => res.data);
  },

  // PDFステータス更新
  updateStatus: async (companyId: string, pdfId: string, status: 'active' | 'inactive') => {
    return api.put(`/admin/companies/${companyId}/pdfs/${pdfId}/status`, { status })
      .then((res) => res.data);
  },
};

export default api; 