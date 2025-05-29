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
  getAll: (page = 1, perPage = 10) => 
    api.get<PaginatedResponse<Company>>('/companies', {
      params: { page, per_page: perPage }
    }).then((res) => res.data),
  getById: (id: string) => api.get<{ data: Company }>(`/companies/${id}`).then((res) => res.data.data),
  create: (data: Omit<Company, 'id' | 'created_at' | 'updated_at'>) =>
    api.post<Company>('/companies', data).then((res) => res.data),
  update: (id: string, data: Partial<Company>) =>
    api.put<Company>(`/companies/${id}`, data).then((res) => res.data),
  delete: (id: string) => api.delete(`/companies/${id}`),
};

export interface PDF {
  id: string;
  title: string;
  file_path: string;
  file_name: string;
  file_size: number;
  mime_type: string;
  status: 'active' | 'inactive';
  metadata: {
    original_name: string;
    uploaded_by: string;
    environment: string;
  };
  created_at: string;
  updated_at: string;
}

export const pdfApi = {
  // PDF一覧を取得
  getAll: (companyId: string) => 
    api.get<PaginatedResponse<PDF>>(`/admin/companies/${companyId}/pdfs`).then((res) => res.data),

  // PDFをアップロード
  upload: (companyId: string, file: File, title: string) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('title', title);

    return api.post<PDF>(`/admin/companies/${companyId}/pdfs`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    }).then((res) => res.data);
  },

  // PDFを削除
  delete: (companyId: string, pdfId: string) =>
    api.delete(`/admin/companies/${companyId}/pdfs/${pdfId}`),

  // PDFのプレビューURLを取得
  getPreviewUrl: (companyId: string, pdfId: string) =>
    api.get<{ url: string }>(`/admin/companies/${companyId}/pdfs/${pdfId}/preview`)
      .then((res) => res.data.url),

  // PDFのダウンロードURLを取得
  getDownloadUrl: (companyId: string, pdfId: string) =>
    api.get<{ url: string }>(`/admin/companies/${companyId}/pdfs/${pdfId}/download`)
      .then((res) => res.data.url),

  // PDF閲覧ログを記録
  logView: async (companyId: string, documentId: string, pageNumber: number, viewDuration: number) => {
    if (!documentId || !companyId) {
      console.error('Missing required parameters:', { companyId, documentId });
      return;
    }
    return api.post(`/companies/${companyId}/pdfs/${documentId}/view-logs`, {
      page_number: pageNumber,
      view_duration: viewDuration,
      company_id: companyId
    });
  },

  // PDF閲覧ログを取得
  getViewLogs: async (companyId: string, documentId: string, groupByPage: boolean = false) => {
    if (!documentId || !companyId) {
      console.error('Missing required parameters:', { companyId, documentId });
      return [];
    }
    try {
      const response = await api.get(`/companies/${companyId}/pdfs/${documentId}/view-logs${groupByPage ? '?group_by_page=true' : ''}`);
      return response.data.data || response.data || [];
    } catch (error) {
      console.error('Failed to fetch view logs:', error);
      return [];
    }
  },

  // 会社の全PDF閲覧ログを取得
  getCompanyViewLogs: async (companyId: string, groupByPage: boolean = false) => {
    if (!companyId) {
      console.error('Missing required parameter: companyId');
      return [];
    }
    try {
      const response = await api.get(`/admin/companies/${companyId}/view-logs${groupByPage ? '?group_by_page=true' : ''}`);
      return response.data.data || response.data || [];
    } catch (error) {
      console.error('Failed to fetch company view logs:', error);
      return [];
    }
  },
};

export default api; 