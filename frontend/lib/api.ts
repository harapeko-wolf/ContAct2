import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// リクエストインターセプター
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
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
      localStorage.removeItem('token');
      window.location.href = '/login';
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
  getById: (id: string) => api.get<Company>(`/companies/${id}`).then((res) => res.data),
  create: (data: Omit<Company, 'id' | 'created_at' | 'updated_at'>) =>
    api.post<Company>('/companies', data).then((res) => res.data),
  update: (id: string, data: Partial<Company>) =>
    api.put<Company>(`/companies/${id}`, data).then((res) => res.data),
  delete: (id: string) => api.delete(`/companies/${id}`),
};

export default api; 