<?php
session_start();
require_once 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHOP.COM - หน้าแรก</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .animated-bg span {
            position: absolute;
            display: block;
            width: 20px;
            height: 20px;
            background: rgba(102, 126, 234, 0.1);
            animation: animate 25s linear infinite;
            bottom: -150px;
        }
        
        .animated-bg span:nth-child(1) {
            left: 25%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
        }
        
        .animated-bg span:nth-child(2) {
            left: 10%;
            width: 20px;
            height: 20px;
            animation-delay: 2s;
            animation-duration: 12s;
        }
        
        .animated-bg span:nth-child(3) {
            left: 70%;
            width: 20px;
            height: 20px;
            animation-delay: 4s;
        }
        
        .animated-bg span:nth-child(4) {
            left: 40%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            animation-duration: 18s;
        }
        
        .animated-bg span:nth-child(5) {
            left: 65%;
            width: 20px;
            height: 20px;
            animation-delay: 0s;
        }
        
        .animated-bg span:nth-child(6) {
            left: 75%;
            width: 110px;
            height: 110px;
            animation-delay: 3s;
        }
        
        .animated-bg span:nth-child(7) {
            left: 35%;
            width: 150px;
            height: 150px;
            animation-delay: 7s;
        }
        
        .animated-bg span:nth-child(8) {
            left: 50%;
            width: 25px;
            height: 25px;
            animation-delay: 15s;
            animation-duration: 45s;
        }
        
        .animated-bg span:nth-child(9) {
            left: 20%;
            width: 15px;
            height: 15px;
            animation-delay: 2s;
            animation-duration: 35s;
        }
        
        .animated-bg span:nth-child(10) {
            left: 85%;
            width: 150px;
            height: 150px;
            animation-delay: 0s;
            animation-duration: 11s;
        }
        
        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }
        
        /* Floating Animation */
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        
        /* Pulse Animation */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        /* Shimmer Effect */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }
        
        /* Glow Effect */
        @keyframes glow {
            0% {
                box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
            }
            50% {
                box-shadow: 0 0 20px rgba(102, 126, 234, 0.8);
            }
            100% {
                box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
            }
        }
        
        /* Slide In Animation */
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(100px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Rotate Animation */
        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        /* Wave Animation */
        @keyframes wave {
            0% {
                transform: rotate(0deg);
            }
            10% {
                transform: rotate(14deg);
            }
            20% {
                transform: rotate(-8deg);
            }
            30% {
                transform: rotate(14deg);
            }
            40% {
                transform: rotate(-4deg);
            }
            50% {
                transform: rotate(10deg);
            }
            60% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(0deg);
            }
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Navbar with Glassmorphism */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            position: sticky;
            top: 0;
            z-index: 1000;
            animation: slideInUp 0.8s ease;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand a {
            color: #667eea;
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 1px;
            position: relative;
            animation: pulse 2s infinite;
        }
        
        .nav-brand a:hover {
            animation: glow 1.5s infinite;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }
        
        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            position: relative;
            padding: 0.5rem 0;
            transition: all 0.3s;
        }
        
        .nav-links a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        .nav-links a:hover::before {
            width: 100%;
        }
        
        .nav-links a:hover {
            color: #667eea;
            transform: translateY(-2px);
        }
        
        /* Search Bar */
        .search-bar {
            max-width: 1200px;
            margin: 1rem auto 0;
            padding: 0 20px;
            display: none;
            animation: slideInUp 0.5s ease;
        }
        
        .search-bar.active {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-bar input {
            flex: 1;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            transform: scale(1.02);
        }
        
        .search-bar button {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .search-bar button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .search-bar button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .search-bar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* Hero Section with Animated Gradient */
        .hero-section {
            background: linear-gradient(-45deg, #667eea, #764ba2, #667eea, #764ba2);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            color: white;
            padding: 6rem 0;
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.1) 10px,
                rgba(255, 255, 255, 0.1) 20px
            );
            animation: moveBackground 20s linear infinite;
        }
        
        @keyframes moveBackground {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(10%, 10%);
            }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: slideInLeft 1s ease;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .hero-content p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            animation: slideInRight 1s ease;
        }
        
        .hero-content .welcome-user {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            color: #ffd700;
            animation: fadeInScale 1s ease;
        }
        
        .welcome-user i {
            animation: wave 2.5s infinite;
            display: inline-block;
        }
        
        .btn-primary {
            display: inline-block;
            padding: 1rem 2.5rem;
            background: #ffd700;
            color: #333;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            animation: pulse 2s infinite;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 30px rgba(255, 215, 0, 0.4);
            background: #ffed4a;
        }
        
        /* Categories Section */
        .categories-section {
            padding: 4rem 0;
            background: transparent;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
            position: relative;
            animation: slideInUp 1s ease;
        }
        
        .section-title:after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 1rem auto;
            border-radius: 2px;
            animation: pulse 2s infinite;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .category-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: block;
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
            animation: fadeInScale 1s ease;
            animation-fill-mode: both;
        }
        
        .category-card:nth-child(1) { animation-delay: 0.1s; }
        .category-card:nth-child(2) { animation-delay: 0.2s; }
        .category-card:nth-child(3) { animation-delay: 0.3s; }
        .category-card:nth-child(4) { animation-delay: 0.4s; }
        .category-card:nth-child(5) { animation-delay: 0.5s; }
        .category-card:nth-child(6) { animation-delay: 0.6s; }
        .category-card:nth-child(7) { animation-delay: 0.7s; }
        .category-card:nth-child(8) { animation-delay: 0.8s; }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: left 0.5s;
        }
        
        .category-card:hover::before {
            left: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-15px) scale(1.05);
            box-shadow: 0 30px 45px rgba(102, 126, 234, 0.3);
            border-color: #667eea;
        }
        
        .category-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            transition: all 0.4s;
            animation: float 3s ease-in-out infinite;
        }
        
        .category-card:hover .category-icon {
            transform: scale(1.1) rotate(5deg);
            animation: pulse 1s infinite;
        }
        
        .category-card h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .category-card:hover h3 {
            color: #667eea;
        }
        
        .category-card p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .category-count {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .category-card:hover .category-count {
            transform: scale(1.1);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }
        
        /* Products Section */
        .products-section {
            padding: 4rem 0;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .product-filters {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideInUp 1s ease;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .filter-group label {
            color: #666;
            font-weight: 500;
        }
        
        .filter-group select {
            padding: 0.5rem 1.5rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-family: inherit;
            cursor: pointer;
            background: white;
            transition: all 0.3s;
        }
        
        .filter-group select:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .product-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            animation: fadeInScale 1s ease;
            animation-fill-mode: both;
        }
        
        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transition: left 0.5s;
            z-index: 1;
        }
        
        .product-card:hover::before {
            left: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-15px) scale(1.03);
            box-shadow: 0 30px 45px rgba(102, 126, 234, 0.3);
        }
        
        .product-image {
            height: 250px;
            overflow: hidden;
            position: relative;
            background: #f8f9fa;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.2) rotate(2deg);
        }
        
        .discount-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: linear-gradient(135deg, #ff6b6b, #ff4444);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.3);
            animation: pulse 2s infinite;
        }
        
        .product-info {
            padding: 1.5rem;
            position: relative;
            z-index: 2;
        }
        
        .product-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .product-card:hover .product-info h3 {
            color: #667eea;
        }
        
        .product-category {
            font-size: 0.9rem;
            color: #999;
            margin-bottom: 0.5rem;
        }
        
        .product-price {
            margin-bottom: 0.5rem;
        }
        
        .current-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #667eea;
            animation: glow 2s infinite;
        }
        
        .old-price {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 0.5rem;
        }
        
        .product-stock {
            font-size: 0.9rem;
            color: #28a745;
        }
        
        .add-to-cart {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .add-to-cart::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .add-to-cart:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .add-to-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* View All Button */
        .view-all-container {
            text-align: center;
            margin-top: 3rem;
        }
        
        .btn-view-all {
            display: inline-block;
            padding: 1.2rem 3rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-view-all::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-view-all:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-view-all:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 35px rgba(102, 126, 234, 0.4);
        }
        
        .btn-view-all i {
            margin-right: 0.5rem;
            animation: rotate 2s linear infinite;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }
        
        .page-btn {
            padding: 0.8rem 1.2rem;
            border: 2px solid #e1e5e9;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .page-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .page-btn:hover::before {
            width: 200px;
            height: 200px;
        }
        
        .page-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
        }
        
        .page-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
            animation: pulse 2s infinite;
        }
        
        /* Footer */
        .footer {
            background: rgba(45, 55, 72, 0.95);
            backdrop-filter: blur(10px);
            color: white;
            padding: 4rem 0 2rem;
            margin-top: 3rem;
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255, 255, 255, 0.02) 10px,
                rgba(255, 255, 255, 0.02) 20px
            );
            animation: moveBackground 20s linear infinite;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
            position: relative;
            z-index: 1;
        }
        
        .footer-col {
            animation: slideInUp 1s ease;
            animation-fill-mode: both;
        }
        
        .footer-col:nth-child(1) { animation-delay: 0.1s; }
        .footer-col:nth-child(2) { animation-delay: 0.2s; }
        .footer-col:nth-child(3) { animation-delay: 0.3s; }
        .footer-col:nth-child(4) { animation-delay: 0.4s; }
        
        .footer-col h4 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .footer-col h4:after {
            content: '';
            display: block;
            width: 50px;
            height: 2px;
            background: #ffd700;
            margin-top: 0.5rem;
            animation: pulse 2s infinite;
        }
        
        .footer-col p {
            color: #cbd5e0;
            line-height: 1.8;
        }
        
        .footer-col ul {
            list-style: none;
        }
        
        .footer-col ul li {
            margin-bottom: 0.5rem;
            transition: transform 0.3s;
        }
        
        .footer-col ul li:hover {
            transform: translateX(10px);
        }
        
        .footer-col ul li a {
            color: #cbd5e0;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-col ul li a:hover {
            color: #ffd700;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            color: white;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .social-links a::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .social-links a:hover::before {
            width: 80px;
            height: 80px;
        }
        
        .social-links a:hover {
            background: #ffd700;
            color: #333;
            transform: translateY(-5px) rotate(360deg);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #4a5568;
            color: #cbd5e0;
            position: relative;
            z-index: 1;
        }
        
        /* Loading Spinner */
        .spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Scroll to Top Button */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            z-index: 99;
            animation: pulse 2s infinite;
            transition: all 0.3s;
        }
        
        .scroll-top:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
        }
        
        .scroll-top.show {
            display: flex;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .hero-content h1 {
                font-size: 2.5rem;
            }
            
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .product-filters {
                flex-direction: column;
                gap: 1rem;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-group select {
                flex: 1;
            }
        }
        
        @media (max-width: 480px) {
            .category-grid {
                grid-template-columns: 1fr;
            }
            
            .product-grid {
                grid-template-columns: 1fr;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php">SHOP.COM</a>
            </div>
            <div class="nav-menu">
                <ul class="nav-links">
                    <li><a href="index.php">หน้าแรก</a></li>
                    <li><a href="category.php">หมวดหมู่</a></li>
                    <li><a href="category.php">สินค้าทั้งหมด</a></li>
                    <li><a href="#contact">ติดต่อเรา</a></li>
                </ul>
                <div class="nav-icons">
                    <a href="#" class="search-icon" onclick="toggleSearch()"><i class="fas fa-search"></i></a>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="user-dropdown">
                            <a href="#" class="user-icon">
                                <i class="fas fa-user-circle"></i>
                                <?php 
                                    $display_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 
                                                   (isset($_SESSION['username']) ? $_SESSION['username'] : 'สมาชิก');
                                    echo htmlspecialchars($display_name);
                                ?>
                            </a>
                            <div class="dropdown-content">
                                <a href="profile.php"><i class="fas fa-user-circle"></i> โปรไฟล์ของฉัน</a>
                                <a href="orders.php"><i class="fas fa-shopping-bag"></i> คำสั่งซื้อของฉัน</a>
                                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="login-btn">เข้าสู่ระบบ</a>
                        <a href="register.php" class="register-btn">สมัครสมาชิก</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Search Bar -->
        <div class="search-bar" id="searchBar">
            <input type="text" id="searchInput" placeholder="ค้นหาสินค้า...">
            <button onclick="searchProducts()"><i class="fas fa-search"></i> ค้นหา</button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>ยินดีต้อนรับสู่ SHOP.COM</h1>
            <p>สินค้าคุณภาพ ราคาถูก จัดส่งไว บริการประทับใจ</p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="welcome-user">
                    <i class="fas fa-hand-peace"></i> สวัสดีคุณ <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?>
                </div>
            <?php endif; ?>
            <a href="category.php" class="btn-primary">เริ่มช้อปปิ้ง</a>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="categories-section">
        <div class="container">
            <h2 class="section-title">หมวดหมู่สินค้า</h2>
            <div class="category-grid">
                <?php
                // ดึงหมวดหมู่จากฐานข้อมูล
                try {
                    $categories = fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order, name LIMIT 8");
                    
                    if(count($categories) > 0) {
                        foreach($categories as $category) {
                            $product_count = fetchOne("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND status = 'active'", [$category['id']])['count'] ?? 0;
                            ?>
                            <a href="category.php?id=<?php echo $category['id']; ?>" class="category-card">
                                <div class="category-icon">
                                    <i class="<?php echo $category['icon'] ?? 'fas fa-tag'; ?>"></i>
                                </div>
                                <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p><?php echo number_format($product_count); ?> สินค้า</p>
                                <span class="category-count">ดูสินค้า</span>
                            </a>
                            <?php
                        }
                    } else {
                        $sample_categories = [
                            ['name' => 'เสื้อผ้า', 'icon' => 'fas fa-tshirt', 'count' => 15],
                            ['name' => 'แฟชั่น', 'icon' => 'fas fa-hat-cowboy', 'count' => 23],
                            ['name' => 'อิเล็กทรอนิกส์', 'icon' => 'fas fa-laptop', 'count' => 42],
                            ['name' => 'เครื่องประดับ', 'icon' => 'fas fa-gem', 'count' => 18],
                            ['name' => 'ของใช้ในบ้าน', 'icon' => 'fas fa-home', 'count' => 31],
                            ['name' => 'สุขภาพและความงาม', 'icon' => 'fas fa-heart', 'count' => 27],
                            ['name' => 'อาหารและเครื่องดื่ม', 'icon' => 'fas fa-utensils', 'count' => 56],
                            ['name' => 'กีฬาและท่องเที่ยว', 'icon' => 'fas fa-futbol', 'count' => 14]
                        ];
                        
                        foreach($sample_categories as $cat) {
                            ?>
                            <a href="category.php?name=<?php echo urlencode($cat['name']); ?>" class="category-card">
                                <div class="category-icon">
                                    <i class="<?php echo $cat['icon']; ?>"></i>
                                </div>
                                <h3><?php echo $cat['name']; ?></h3>
                                <p><?php echo $cat['count']; ?> สินค้า</p>
                                <span class="category-count">ดูสินค้า</span>
                            </a>
                            <?php
                        }
                    }
                } catch(Exception $e) {
                    ?>
                    <a href="category.php?name=เสื้อผ้า" class="category-card">
                        <div class="category-icon"><i class="fas fa-tshirt"></i></div>
                        <h3>เสื้อผ้า</h3>
                        <p>15 สินค้า</p>
                        <span class="category-count">ดูสินค้า</span>
                    </a>
                    <a href="category.php?name=อิเล็กทรอนิกส์" class="category-card">
                        <div class="category-icon"><i class="fas fa-laptop"></i></div>
                        <h3>อิเล็กทรอนิกส์</h3>
                        <p>42 สินค้า</p>
                        <span class="category-count">ดูสินค้า</span>
                    </a>
                    <a href="category.php?name=ของใช้ในบ้าน" class="category-card">
                        <div class="category-icon"><i class="fas fa-home"></i></div>
                        <h3>ของใช้ในบ้าน</h3>
                        <p>31 สินค้า</p>
                        <span class="category-count">ดูสินค้า</span>
                    </a>
                    <a href="category.php?name=สุขภาพ" class="category-card">
                        <div class="category-icon"><i class="fas fa-heart"></i></div>
                        <h3>สุขภาพ</h3>
                        <p>27 สินค้า</p>
                        <span class="category-count">ดูสินค้า</span>
                    </a>
                    <?php
                }
                ?>
            </div>
            
            <div class="view-all-container">
                <a href="category.php" class="btn-view-all">
                    <i class="fas fa-th-large"></i> ดูหมวดหมู่ทั้งหมด
                </a>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="products-section">
        <div class="container">
            <h2 class="section-title">สินค้ามาใหม่</h2>
            
            <div class="product-filters">
                <div class="filter-group">
                    <label><i class="fas fa-sort"></i> เรียงตาม:</label>
                    <select onchange="sortProducts(this.value)">
                        <option value="newest">มาใหม่ล่าสุด</option>
                        <option value="price-low">ราคาต่ำไปสูง</option>
                        <option value="price-high">ราคาสูงไปต่ำ</option>
                        <option value="popular">ยอดนิยม</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-eye"></i> แสดง:</label>
                    <select onchange="showPerPage(this.value)">
                        <option value="12">12 ชิ้น</option>
                        <option value="24">24 ชิ้น</option>
                        <option value="36">36 ชิ้น</option>
                    </select>
                </div>
            </div>
            
            <div class="product-grid" id="productGrid">
                <?php
                try {
                    $products = fetchAll("SELECT * FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 4");
                    
                    if(count($products) > 0) {
                        foreach($products as $product) {
                            ?>
                            <div class="product-card">
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <div class="product-image">
                                        <img src="<?php echo showImage($product['image'], 'products', 'default-product.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                            <?php $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>
                                            <span class="discount-badge">-<?php echo $discount; ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <div class="product-price">
                                            <span class="current-price">฿<?php echo number_format($product['price']); ?></span>
                                            <?php if($product['original_price'] && $product['original_price'] > $product['price']): ?>
                                                <span class="old-price">฿<?php echo number_format($product['original_price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-stock">
                                            <i class="fas fa-box"></i> คงเหลือ <?php echo $product['stock']; ?> ชิ้น
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php
                        }
                    } else {
                        $sample_products = [
                            ['name' => 'เสื้อยืดคอปก ผู้ชาย', 'price' => 299],
                            ['name' => 'หูฟังไร้สาย Bluetooth', 'price' => 1290],
                            ['name' => 'กระเป๋าสะพายหนังแท้', 'price' => 1890],
                            ['name' => 'นาฬิกาข้อมือ Smart Watch', 'price' => 890]
                        ];
                        
                        foreach($sample_products as $product) {
                            ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="https://via.placeholder.com/300x300" alt="<?php echo $product['name']; ?>">
                                </div>
                                <div class="product-info">
                                    <h3><?php echo $product['name']; ?></h3>
                                    <div class="product-price">
                                        <span class="current-price">฿<?php echo number_format($product['price']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                } catch(Exception $e) {
                    ?>
                    <div class="product-card">
                        <div class="product-image"><img src="https://via.placeholder.com/300x300" alt="สินค้า"></div>
                        <div class="product-info">
                            <h3>เสื้อยืดคอปก ผู้ชาย</h3>
                            <div class="product-price">฿299</div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <div class="view-all-container">
                <a href="category.php" class="btn-view-all">
                    <i class="fas fa-box"></i> ดูสินค้าทั้งหมด
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>เกี่ยวกับเรา</h4>
                    <p>ร้านค้าออนไลน์ที่มอบประสบการณ์การช้อปปิ้งที่ดีที่สุด ด้วยสินค้าคุณภาพ ราคายุติธรรม และบริการที่ประทับใจ</p>
                </div>
                <div class="footer-col">
                    <h4>ลิงก์ที่เกี่ยวข้อง</h4>
                    <ul>
                        <li><a href="category.php">หมวดหมู่สินค้า</a></li>
                        <li><a href="#">วิธีการสั่งซื้อ</a></li>
                        <li><a href="#">นโยบายการจัดส่ง</a></li>
                        <li><a href="#">นโยบายการคืนสินค้า</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>ติดตามเรา</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-line"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>ช่องทางการชำระเงิน</h4>
                    <div class="payment-methods">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> SHOP.COM - ร้านค้าออนไลน์. สงวนลิขสิทธิ์.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" onclick="scrollToTop()" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
        // Toggle search bar
        function toggleSearch() {
            const searchBar = document.getElementById('searchBar');
            searchBar.classList.toggle('active');
            if(searchBar.classList.contains('active')) {
                document.getElementById('searchInput').focus();
            }
        }
        
        // Search products
        function searchProducts() {
            const searchTerm = document.getElementById('searchInput').value;
            if(searchTerm.trim()) {
                window.location.href = 'category.php?search=' + encodeURIComponent(searchTerm);
            }
        }
        
        // Enter key for search
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                searchProducts();
            }
        });
        
        // Sort products
        function sortProducts(value) {
            console.log('Sort by:', value);
        }
        
        // Show per page
        function showPerPage(value) {
            console.log('Show per page:', value);
        }
        
        // Scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Show/hide scroll button
        window.addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scrollTop');
            if(window.scrollY > 500) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
        });
    </script>
</body>
</html>