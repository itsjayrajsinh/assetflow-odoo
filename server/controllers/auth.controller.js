import asyncHandler from 'express-async-handler';
import crypto from 'crypto';
import User from '../models/User.js';
import { signToken } from '../utils/token.js';
import { sendMail } from '../services/mail.service.js';
import { logActivity } from '../services/activity.service.js';

const shape = (u) => ({
  id: u._id,
  name: u.name,
  email: u.email,
  role: u.role,
  department: u.department,
  avatar: u.avatar,
});

export const signup = asyncHandler(async (req, res) => {
  const { name, email, password } = req.body;
  if (!name || !email || !password) {
    res.status(400);
    throw new Error('Name, email and password required');
  }
  const exists = await User.findOne({ email });
  if (exists) {
    res.status(409);
    throw new Error('Email already registered');
  }
  const user = await User.create({ name, email, password, role: 'employee' });
  await logActivity({ user: user._id, action: 'signup', entity: 'User', entityId: user._id });
  res.status(201).json({ token: signToken(user._id), user: shape(user) });
});

export const login = asyncHandler(async (req, res) => {
  const { email, password } = req.body;
  const user = await User.findOne({ email }).select('+password').populate('department', 'name');
  if (!user || !(await user.matchPassword(password))) {
    res.status(401);
    throw new Error('Invalid credentials');
  }
  if (!user.isActive) {
    res.status(403);
    throw new Error('Account disabled');
  }
  res.json({ token: signToken(user._id), user: shape(user) });
});

export const me = asyncHandler(async (req, res) => {
  res.json({ user: shape(req.user) });
});

export const forgotPassword = asyncHandler(async (req, res) => {
  const { email } = req.body;
  const user = await User.findOne({ email });
  if (!user) return res.json({ ok: true }); // silent
  const token = crypto.randomBytes(32).toString('hex');
  user.resetPasswordToken = crypto.createHash('sha256').update(token).digest('hex');
  user.resetPasswordExpires = Date.now() + 60 * 60 * 1000;
  await user.save();
  const url = `${process.env.CLIENT_URL}/reset-password/${token}`;
  await sendMail({
    to: user.email,
    subject: 'AssetFlow password reset',
    html: `<p>Reset link (1h): <a href="${url}">${url}</a></p>`,
  });
  res.json({ ok: true });
});

export const resetPassword = asyncHandler(async (req, res) => {
  const hashed = crypto.createHash('sha256').update(req.params.token).digest('hex');
  const user = await User.findOne({
    resetPasswordToken: hashed,
    resetPasswordExpires: { $gt: Date.now() },
  });
  if (!user) {
    res.status(400);
    throw new Error('Invalid or expired token');
  }
  user.password = req.body.password;
  user.resetPasswordToken = undefined;
  user.resetPasswordExpires = undefined;
  await user.save();
  res.json({ ok: true });
});
