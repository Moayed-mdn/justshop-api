# Auth Surface Classification

**Version:** 1.0  
**Status:** VERIFIED_COMPLETE  
**Wave:** 4

## Overview

This document classifies all authentication surfaces by their intended actor domain and current authority state.

## 1. Merchant Auth Surface

Routes: `/api/v1/users/auth/*` (Legacy/Merchant)

| Endpoint | Purpose | Intended Actor | Current Authority |
|----------|---------|----------------|-------------------|
| `/login` | Merchant Login | Merchant | Shared (Web) |
| `/register`| Merchant Register | Merchant | Shared (Web) |
| `/logout` | Merchant Logout | Merchant | Shared (Web) |
| `/me` | Merchant Identity | Merchant | Shared (Web) |
| `/bootstrap`| Merchant State | Merchant | Shared (Web) |

## 2. Customer Auth Surface

Routes: `/api/v1/storefront/account/*` (Isolated)

| Endpoint | Purpose | Intended Actor | Current Authority |
|----------|---------|----------------|-------------------|
| `/login` | Customer Login | Customer | Shared (Web) |
| `/register`| Customer Register | Customer | Shared (Web) |
| `/logout` | Customer Logout | Customer | Shared (Web) |
| `/me` | Customer Identity | Customer | Shared (Web) |
| `/bootstrap`| Customer State | Customer | Shared (Web) |

## 3. Platform Admin Surface

Routes: `/api/v1/admin/*`

| Surface | Intended Actor | Current Authority |
|---------|----------------|-------------------|
| Store Management | Merchant Admin | Shared (Web) |
| Platform Management| Super Admin | Shared (Web) |

## 4. Shared Transitional Surface

| Surface | Purpose | Current Authority |
|---------|---------|-------------------|
| `/sanctum/csrf-cookie` | CSRF Setup | Shared (Web) |
| `/api/stripe/webhook` | External Webhook | Stateless/Signature |
| `/api/v1/public/*` | Public Content | Guest/No Auth |
