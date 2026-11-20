import axios from 'axios'

const apiEnv = (import.meta as unknown as { env?: { VITE_API_URL?: string } }).env
export const api = axios.create({ baseURL: apiEnv?.VITE_API_URL ?? 'http://127.0.0.1:8000/api/v1' })
api.interceptors.request.use(config => { const token = localStorage.getItem('mauzohub_token'); if (token) config.headers.Authorization = `Bearer ${token}`; return config })
api.interceptors.response.use(r => r, e => { if (e.response?.status === 401) { localStorage.removeItem('mauzohub_token'); if (location.pathname !== '/login') location.href = '/login' } return Promise.reject(e) })
export function apiError(error: any) { return error?.response?.data?.message ?? Object.values(error?.response?.data?.errors ?? {})?.flat?.()?.[0] ?? 'Something went wrong. Please try again.' }
