import Notification from '../models/Notification.js';

let ioRef = null;
export const attachIO = (io) => {
  ioRef = io;
};

export async function notify({ user, type, title, message, link }) {
  const doc = await Notification.create({ user, type, title, message, link });
  if (ioRef) ioRef.to(`user:${user.toString()}`).emit('notification', doc);
  return doc;
}
